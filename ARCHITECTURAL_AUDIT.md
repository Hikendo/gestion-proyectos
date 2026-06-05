# 🏗️ Architectural Audit: Permissions Matrix & Async File Upload Workflow

---

## 1. Architectural Red Flags & Vulnerabilities

### 1.1 RBAC/ABAC Boundary Confusion

| # | Vulnerability | Severity | Impact |
|---|---|---|---|
| **V1** | `BlockerPolicy@create` returns `true` for **any authenticated user** | 🔴 **Critical** | Any user (even non-members) can create blockers on any project. No `can('blocker.create')` check, no project membership validation. |
| **V2** | `BlockerController@uploadAttachments` authorizes with `$this->authorize('update', $project)` instead of a `manageAttachments` policy | 🔴 **Critical** | Any user with `project.edit` can upload attachments to blockers. The BlockerPolicy has no `manageAttachments` method at all. |
| **V3** | `TaskPolicy@update` allows Developer/QA to edit **any field** via `task.edit-own` | 🟠 **High** | A developer could change `assigned_to`, `priority`, `estimated_hours` on their own task — not just status. The policy doesn't restrict *which fields* can be mutated. |
| **V4** | `TicketPolicy@update` allows Developer/QA/Support to edit **any field** on their own tickets | 🟠 **High** | Same as V3 — no field-level restriction. A developer could escalate priority or reassign their ticket. |
| **V5** | `ProjectMemberRole` enum has `task.log-time` for Manager but **not** for Developer | 🟡 **Medium** | The seeder grants `task.log-time` to Developer, but the enum for `self::Developer` does NOT include it. Inconsistency between Spatie roles and project-level permissions. |
| **V6** | `ProjectMemberRole` enum for `self::Manager` includes `task.log-time` but the seeder does NOT grant it to `project-manager` | 🟡 **Medium** | PMs can log time via project membership but not via Spatie role. Inconsistency. |
| **V7** | `BlockerPolicy@create` has no `before()` bypass for super-admin on `create` | 🟡 **Medium** | The `before()` method returns `true` for super-admin on all *instance* methods, but `create()` is a static method — super-admin could be blocked. |
| **V8** | No `manageAttachments` method exists in `BlockerPolicy` or `DeliverablePolicy` | 🟠 **High** | The `AttachmentController@destroy` falls back to `Gate::authorize('delete', $parent)` for these models, which may not exist or may grant unintended access. |

### 1.2 The "Orphan Attachment" Race Condition Bug

**Current flow (broken):**

```
User opens "New Task" form
  → User selects files in <VFileInput>
  → Files are held in memory (pendingFiles ref)
  → User clicks "Guardar"
  → Frontend builds FormData with { ...formData, attachments[] }
  → POST /api/projects/{project}/tasks  (with multipart/form-data)
  → Backend creates Task, then uploads attachments
```

**The bug:** If the user selects files and then **cancels** or **navigates away**, the files are only in memory — they're lost but no garbage is created. However, if the frontend were to upload files **before** form submission (e.g., auto-upload on file select), the attachments would be created with `attachable_id = null` or linked to a non-existent record.

**Current state:** The bug is **latent** — the frontend currently holds files in memory and only sends them with the form submission. But the architecture is **fragile**: any future change to "upload on file select" (common UX pattern) would trigger the orphan bug.

**Root cause:** The `Attachment` model has no mechanism for "pending" or "draft" state. The `attachable_id` is a required foreign key with no nullable fallback, and there's no garbage collection for abandoned uploads.

---

## 2. Refined Granular Permission Matrix

### 2.1 New Permissions to Add

```php
// ── Attachments (temporary/draft) ──────────────────────────
'attachment.upload-temporary',   // upload files before parent exists
'attachment.claim',              // claim orphaned attachments to a new parent
'attachment.delete-own',         // delete own temporary attachments
'attachment.delete-any',         // delete any attachment (PM only)

// ── Tasks (field-level) ────────────────────────────────────
'task.edit-content',             // title, description, acceptance criteria (PM only)
'task.edit-own-fields',          // own task: only status, log time, progress
'task.edit-own-status',          // own task: only status transitions
'task.edit-own-time',            // own task: only log time

// ── Tickets (field-level) ──────────────────────────────────
'ticket.edit-own-fields',        // own ticket: only subject, description (not priority/assignee)
'ticket.edit-own-status',        // own ticket: only status transitions
'ticket.edit-any-fields',        // any ticket: all fields (PM only)
```

### 2.2 Final Permission Matrix

| Permission | Super Admin | PM | Developer | QA | Support | Client |
|---|---|---|---|---|---|---|
| `attachment.upload-temporary` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `attachment.claim` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `attachment.delete-own` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `attachment.delete-any` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `task.edit-content` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `task.edit-own-fields` | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ |
| `task.edit-own-status` | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ |
| `task.edit-own-time` | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| `ticket.edit-own-fields` | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| `ticket.edit-own-status` | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| `ticket.edit-any-fields` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## 3. Attachment State Workflow Solution

### 3.1 Draft/Session Token Pattern (Recommended)

**Architecture:**

```
┌─────────────────────────────────────────────────────────────┐
│                    DRAFT SESSION FLOW                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. User opens "New Task" form                              │
│     → Frontend calls: POST /api/v1/draft-sessions           │
│     → Backend returns: { draft_token: "uuid-xxx" }          │
│     → Token stored in component state (not persisted)       │
│                                                             │
│  2. User selects files                                      │
│     → Frontend calls: POST /api/v1/attachments/temporary    │
│       Body: { draft_token: "uuid-xxx", files: [...] }       │
│     → Backend creates Attachment with:                      │
│       - attachable_type: null                               │
│       - attachable_id: null                                 │
│       - draft_token: "uuid-xxx"                             │
│       - uploaded_by: auth user ID                           │
│       - status: 'temporary'                                 │
│     → Returns: [{ uuid, original_name, size, ... }]         │
│                                                             │
│  3. User submits form                                       │
│     → Frontend sends: POST /api/projects/{id}/tasks         │
│       Body: { ..., draft_token: "uuid-xxx" }                │
│     → Backend creates Task, then:                           │
│       Attachment::where('draft_token', $token)              │
│                 ->where('uploaded_by', auth()->id())         │
│                 ->update([                                   │
│                     'attachable_type' => Task::class,        │
│                     'attachable_id' => $task->id,            │
│                     'status' => 'permanent',                 │
│                     'draft_token' => null,                   │
│                   ]);                                        │
│                                                             │
│  4. User cancels form                                       │
│     → Frontend calls: DELETE /api/v1/draft-sessions/{token} │
│     → Backend deletes all temporary attachments             │
│       with that draft_token and uploaded_by                 │
│                                                             │
│  5. Garbage Collection (Cron)                               │
│     → Daily: DELETE attachments WHERE status='temporary'    │
│       AND created_at < now() - 24 hours                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 3.2 Database Changes

```php
// Add to attachments table migration
Schema::table('attachments', function (Blueprint $table) {
    $table->string('draft_token')->nullable()->index();
    $table->string('status')->default('permanent'); // 'temporary' | 'permanent'
    
    // Make attachable columns nullable for draft state
    $table->unsignedBigInteger('attachable_id')->nullable()->change();
    $table->string('attachable_type')->nullable()->change();
});
```

### 3.3 Permission Ownership During Draft State

| State | Who can view | Who can delete | Who can claim |
|---|---|---|---|
| **Temporary** (draft_token set) | Only `uploaded_by` user | `uploaded_by` user (via `attachment.delete-own`) or PM (via `attachment.delete-any`) | Only PM (via `attachment.claim`) |
| **Permanent** (attachable_id set) | Anyone with `view` on parent | PM/owner (via `manageAttachments` policy) | N/A |

### 3.4 New API Endpoints

```php
// routes/api/attachments.php

// Draft session management
Route::post('draft-sessions', [DraftSessionController::class, 'store']);     // Generate token
Route::delete('draft-sessions/{token}', [DraftSessionController::class, 'destroy']); // Cleanup

// Temporary attachment upload
Route::post('attachments/temporary', [AttachmentController::class, 'uploadTemporary']);

// Claim attachments to a parent
Route::post('attachments/claim', [AttachmentController::class, 'claim']);
```

---

## 4. Contextual Logic & Laravel Policies Mapping

### 4.1 TaskPolicy (Refined)

```php
class TaskPolicy
{
    public function update(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) return false;

        // PM / Owner: full content edit
        if ($task->project->owner_id === $user->id || $user->hasProjectRole($task->project, 'manager')) {
            return $user->canForProject($task->project, 'task.edit-content');
        }

        // Developer / QA: field-restricted edit
        if ($user->canForProject($task->project, 'task.edit-own-fields')
            && $task->assigned_to === $user->id) {
            return true; // Policy allows; field validation happens in FormRequest
        }

        return false;
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) return false;

        return $user->canForProject($task->project, 'task.edit-own-status')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager')
                || $task->assigned_to === $user->id);
    }

    public function manageAttachments(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.manage-attachments')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager'));
    }
}
```

### 4.2 TicketPolicy (Refined)

```php
class TicketPolicy
{
    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status->isClosed()) return false;

        // PM / Owner: full edit
        if ($ticket->project->owner_id === $user->id || $user->hasProjectRole($ticket->project, 'manager')) {
            return $user->canForProject($ticket->project, 'ticket.edit-any-fields');
        }

        // Client: own tickets, only if Open
        if ($user->hasProjectRole($ticket->project, 'client')) {
            return $user->canForProject($ticket->project, 'ticket.edit-own-fields')
                && $ticket->created_by === $user->id
                && $ticket->status->isOpen();
        }

        // Developer / QA / Support: own tickets
        return $user->canForProject($ticket->project, 'ticket.edit-own-fields')
            && $ticket->created_by === $user->id;
    }

    public function manageAttachments(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.manage-attachments')
            && ($ticket->project->owner_id === $user->id
                || $user->hasProjectRole($ticket->project, 'manager'));
    }
}
```

### 4.3 BlockerPolicy (Fixed)

```php
class BlockerPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super-admin')) return true;
        return null;
    }

    public function create(User $user): bool
    {
        return $user->can('blocker.create');
    }

    public function update(User $user, Blocker $blocker): bool
    {
        if ($blocker->resolved) return false;

        return $user->canForProject($blocker->project, 'blocker.edit')
            && ($blocker->project->owner_id === $user->id
                || $user->hasProjectRole($blocker->project, 'manager'));
    }

    public function resolve(User $user, Blocker $blocker): bool
    {
        if ($blocker->resolved) return false;

        return $user->canForProject($blocker->project, 'blocker.resolve')
            && ($blocker->project->owner_id === $user->id
                || $user->hasProjectRole($blocker->project, 'manager'));
    }

    public function manageAttachments(User $user, Blocker $blocker): bool
    {
        return $user->canForProject($blocker->project, 'blocker.edit')
            && ($blocker->project->owner_id === $user->id
                || $user->hasProjectRole($blocker->project, 'manager'));
    }
}
```

### 4.4 Field-Level Validation in FormRequests

Spatie RBAC + Policies handle *who* can act. For *what fields* they can change, use FormRequests:

```php
class UpdateTaskRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user();
        $task = $this->route('task');

        $rules = [];

        // PM can edit everything
        if ($task->project->owner_id === $user->id || $user->hasProjectRole($task->project, 'manager')) {
            $rules = [
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string'],
                'priority' => ['sometimes', 'nullable', 'string', 'in:low,medium,high,critical'],
                'estimated_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
                'due_date' => ['sometimes', 'nullable', 'date'],
            ];
        }
        // Developer/QA: only status and progress
        elseif ($task->assigned_to === $user->id) {
            $rules = [
                'status' => ['sometimes', 'string', 'in:in_progress,review,done'],
                'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
                'worked_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            ];
        }

        return $rules;
    }
}
```

---

## 5. Event-Driven Flows & Garbage Collection

### 5.1 Domain Events

```php
// Event: AttachmentsClaimed
// Fired when: Temporary attachments are linked to a newly created parent
class AttachmentsClaimed
{
    public function __construct(
        public Model $parent,           // Task, Ticket, or Blocker
        public array $attachmentUuids,  // UUIDs of claimed attachments
        public User $claimedBy,         // User who performed the claim
    ) {}
}

// Listener: NotifyProjectMembers
class NotifyAttachmentsClaimed
{
    public function handle(AttachmentsClaimed $event): void
    {
        // Notify PM that files were attached to the new record
        Notification::create([
            'user_id' => $event->parent->project->owner_id,
            'title' => 'Archivos adjuntos vinculados',
            'body' => count($event->attachmentUuids) . ' archivo(s) vinculados a ' . class_basename($event->parent),
            'type' => 'attachment_claimed',
        ]);
    }
}
```

### 5.2 Garbage Collection (Scheduled Task)

```php
// app/Console/Commands/ClearOrphanAttachments.php
class ClearOrphanAttachments extends Command
{
    protected $signature = 'attachments:clear-orphans';
    protected $description = 'Delete temporary attachments older than 24 hours';

    public function handle(): void
    {
        $cutoff = now()->subHours(24);

        $orphans = Attachment::where('status', 'temporary')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($orphans as $attachment) {
            Storage::disk('local')->delete($attachment->disk_path);
            $attachment->delete();
            $this->info("Deleted orphan: {$attachment->uuid}");
        }

        $this->info("Cleared {$orphans->count()} orphan attachments.");
    }
}

// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('attachments:clear-orphans')->daily();
}
```

---

## 6. Refactored Seeder Code

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── ALL PERMISSIONS ─────────────────────────────────────────────
        $permissions = [
            // Dashboard
            'dashboard.view',

            // Projects
            'project.view', 'project.create', 'project.edit', 'project.delete',
            'project.assign-members', 'project.manage-attachments',

            // Phases
            'phase.view', 'phase.create', 'phase.edit', 'phase.delete',

            // Tasks (granular)
            'task.view', 'task.create',
            'task.edit-content',       // PM only: title, description, criteria
            'task.edit-own-fields',    // Dev/QA: status, progress, worked_hours
            'task.edit-own-status',    // Dev/QA: status transitions only
            'task.edit-own-time',      // Dev: log time only
            'task.delete', 'task.assign',
            'task.manage-attachments',

            // Tickets (granular)
            'ticket.view', 'ticket.create',
            'ticket.edit-own-fields',  // Own tickets: subject, description
            'ticket.edit-own-status',  // Own tickets: status transitions
            'ticket.edit-any-fields',  // PM: any ticket, all fields
            'ticket.delete', 'ticket.assign',
            'ticket.manage-attachments',

            // Risks
            'risk.view', 'risk.create', 'risk.edit', 'risk.delete',

            // Blockers
            'blocker.view', 'blocker.create', 'blocker.edit', 'blocker.resolve',

            // Milestones
            'milestone.view', 'milestone.create', 'milestone.edit', 'milestone.delete',

            // Deliverables
            'deliverable.view', 'deliverable.create', 'deliverable.edit', 'deliverable.approve',

            // Objectives
            'objective.view', 'objective.create', 'objective.edit',

            // Metrics & Reports
            'metrics.view', 'reports.view',

            // Users
            'user.view', 'user.create', 'user.edit', 'user.delete',

            // Attachments (draft/temporary workflow)
            'attachment.upload-temporary',
            'attachment.claim',
            'attachment.delete-own',
            'attachment.delete-any',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── SUPER ADMIN ─────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->syncPermissions(Permission::all());

        // ── PROJECT MANAGER ─────────────────────────────────────────────
        $projectManager = Role::firstOrCreate(['name' => 'project-manager']);
        $projectManager->syncPermissions([
            'dashboard.view',
            'project.view', 'project.create', 'project.edit',
            'project.assign-members', 'project.manage-attachments',
            'phase.view', 'phase.create', 'phase.edit', 'phase.delete',
            'task.view', 'task.create', 'task.edit-content', 'task.delete',
            'task.assign', 'task.manage-attachments',
            'ticket.view', 'ticket.create', 'ticket.edit-any-fields',
            'ticket.delete', 'ticket.assign', 'ticket.manage-attachments',
            'risk.view', 'risk.create', 'risk.edit', 'risk.delete',
            'blocker.view', 'blocker.create', 'blocker.edit', 'blocker.resolve',
            'milestone.view', 'milestone.create', 'milestone.edit', 'milestone.delete',
            'deliverable.view', 'deliverable.create', 'deliverable.edit', 'deliverable.approve',
            'objective.view', 'objective.create', 'objective.edit',
            'metrics.view', 'reports.view',
            'user.view',
            'attachment.upload-temporary', 'attachment.claim',
            'attachment.delete-own', 'attachment.delete-any',
        ]);

        // ── DEVELOPER ───────────────────────────────────────────────────
        $developer = Role::firstOrCreate(['name' => 'developer']);
        $developer->syncPermissions([
            'dashboard.view',
            'project.view',
            'phase.view',
            'task.view', 'task.create',
            'task.edit-own-fields',   // status, progress, worked_hours
            'task.edit-own-status',   // status transitions
            'task.edit-own-time',     // log time
            'ticket.view', 'ticket.create',
            'ticket.edit-own-fields', 'ticket.edit-own-status',
            'risk.view',
            'blocker.view', 'blocker.create',
            'milestone.view', 'deliverable.view', 'objective.view',
            'metrics.view',
            'attachment.upload-temporary', 'attachment.delete-own',
        ]);

        // ── QA ──────────────────────────────────────────────────────────
        $qa = Role::firstOrCreate(['name' => 'qa']);
        $qa->syncPermissions([
            'dashboard.view',
            'project.view',
            'phase.view',
            'task.view', 'task.create',
            'task.edit-own-fields',
            'task.edit-own-status',
            'ticket.view', 'ticket.create',
            'ticket.edit-own-fields', 'ticket.edit-own-status',
            'risk.view',
            'blocker.view', 'blocker.create',
            'milestone.view', 'deliverable.view', 'objective.view',
            'metrics.view',
            'attachment.upload-temporary', 'attachment.delete-own',
        ]);

        // ── SUPPORT ─────────────────────────────────────────────────────
        $support = Role::firstOrCreate(['name' => 'support']);
        $support->syncPermissions([
            'dashboard.view',
            'project.view',
            'task.view',
            'ticket.view', 'ticket.create',
            'ticket.edit-own-fields', 'ticket.edit-own-status',
            'ticket.assign',
            'blocker.view',
            'user.view',
            'attachment.upload-temporary', 'attachment.delete-own',
        ]);

        // ── CLIENT ──────────────────────────────────────────────────────
        $client = Role::firstOrCreate(['name' => 'client']);
        $client->syncPermissions([
            'dashboard.view',
            'project.view',
            'ticket.view', 'ticket.create',
            'ticket.edit-own-fields', 'ticket.edit-own-status',
            'milestone.view', 'deliverable.view', 'objective.view',
            'metrics.view', 'reports.view',
        ]);

        if ($this->command) {
            $this->command->info('Roles y permisos creados correctamente.');
        }
    }
}
```

---

## 7. Summary of Required Changes

| # | File | Change |
|---|---|---|
| 1 | `RolesAndPermissionsSeeder.php` | Add `attachment.*` permissions, split `task.edit-own` into `task.edit-own-fields/status/time`, split `ticket.edit-own` into `ticket.edit-own-fields/status` |
| 2 | `ProjectMemberRole.php` | Sync with new permissions, fix `task.log-time` inconsistency |
| 3 | `TaskPolicy.php` | Add `manageAttachments()`, refine `update()` for field-level |
| 4 | `TicketPolicy.php` | Add `manageAttachments()`, refine `update()` for field-level |
| 5 | `BlockerPolicy.php` | Fix `create()` to check permission, add `manageAttachments()` |
| 6 | `DeliverablePolicy.php` | Add `manageAttachments()` |
| 7 | `BlockerController.php` | Fix `uploadAttachments` to use `manageAttachments` policy |
| 8 | `AttachmentController.php` | Add `uploadTemporary()` and `claim()` methods |
| 9 | New: `DraftSessionController.php` | Generate and clean up draft tokens |
| 10 | New: `ClearOrphanAttachments.php` | Daily garbage collection command |
| 11 | `Attachment.php` model | Add `draft_token`, `status` fields; make `attachable_*` nullable |
| 12 | Migration | Alter `attachments` table for nullable morphs + new columns |
| 13 | `UpdateTaskRequest.php` | Add field-level validation rules based on user role |
| 14 | `UpdateTicketRequest.php` | Add field-level validation rules based on user role |
| 15 | Frontend composables | Add draft session initialization on form mount |
| 16 | Frontend services | Add `uploadTemporary()` and `claimAttachments()` API calls |
