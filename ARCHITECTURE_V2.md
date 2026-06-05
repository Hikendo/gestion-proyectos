# 🏛️ Principal Enterprise Architecture: Hybrid RBAC+ABAC, Field-Locking & Async Attachment Workflow

---

## Table of Contents

1. [Moodle-Style Permissions Architecture](#1-moodle-style-permissions-architecture)
2. [Frontend Field-Locking Scheme](#2-frontend-field-locking-scheme)
3. [Secure Async Attachment Lifecycle](#3-secure-async-attachment-lifecycle)
4. [Laravel Policy Blueprints](#4-laravel-policy-blueprints)
5. [Refactored RolesAndPermissionsSeeder](#5-refactored-rolesandpermissionsseeder)

---

## 1. Moodle-Style Permissions Architecture

### 1.1 The Three-Layer Permission Model

```
┌─────────────────────────────────────────────────────────────────────┐
│                    PERMISSION RESOLUTION ORDER                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  1. SUPER-ADMIN OVERRIDE                                            │
│     → Policy::before() returns true → GRANT ALL                     │
│                                                                     │
│  2. USER-SPECIFIC DIRECT PERMISSIONS (Override Layer)               │
│     → Spatie: $user->hasDirectPermission('task.edit-content')       │
│     → Stored in model_has_permissions (user_id + permission_id)     │
│     → Highest priority after super-admin                            │
│                                                                     │
│  3. ROLE-BASED PERMISSIONS (Template Layer)                         │
│     → Spatie: $user->hasRole('developer') → role_has_permissions    │
│     → Stored in role_has_permissions (role_id + permission_id)      │
│     → Lowest priority, acts as baseline template                    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

**Key Insight:** Spatie's `HasPermissions` trait natively supports this hierarchy. `$user->can('permission')` checks direct permissions first, then role permissions. No custom code needed for the resolution order.

### 1.2 Database Schema (Spatie Native Tables)

```sql
-- Role templates (baseline)
roles: id, name, guard_name, created_at, updated_at
role_has_permissions: permission_id, role_id

-- User-specific overrides (Moodle-style)
model_has_permissions: permission_id, model_type, model_id
model_has_roles: role_id, model_type, model_id

-- Permission catalog
permissions: id, name, guard_name, created_at, updated_at
```

### 1.3 API Design: Admin "Role & Permission Customizer"

#### GET /api/admin/permissions — List all available permissions

```json
{
  "status": true,
  "items": {
    "permissions": [
      { "name": "task.view", "group": "tasks", "label": "Ver tareas" },
      { "name": "task.edit-content", "group": "tasks", "label": "Editar contenido de tareas" },
      { "name": "task.edit-own-fields", "group": "tasks", "label": "Editar campos propios de tarea" }
    ],
    "groups": ["dashboard", "projects", "tasks", "tickets", "blockers", "attachments"]
  }
}
```

#### GET /api/admin/roles — List roles with their permissions

```json
{
  "status": true,
  "items": [
    {
      "id": 1,
      "name": "developer",
      "label": "Developer",
      "guard_name": "web",
      "permissions": ["task.view", "task.edit-own-fields", "ticket.view", ...]
    }
  ]
}
```

#### PUT /api/admin/roles/{role} — Update role template permissions

```json
// Request
{
  "permissions": ["task.view", "task.edit-own-fields", "ticket.view", "ticket.create"]
}

// Backend logic
$role = Role::findByName($request->name);
$role->syncPermissions($request->permissions);
```

#### GET /api/admin/users/{user}/permissions — Get user's effective permissions + overrides

```json
{
  "status": true,
  "items": {
    "user": {
      "id": 5,
      "name": "Juan Developer",
      "roles": ["developer"]
    },
    "role_permissions": ["task.view", "task.edit-own-fields", "ticket.view"],
    "direct_permissions": ["project.edit"],  // <-- Override granted by admin
    "effective_permissions": ["task.view", "task.edit-own-fields", "ticket.view", "project.edit"]
  }
}
```

#### PUT /api/admin/users/{user}/permissions — Grant/revoke user-specific overrides

```json
// Request: Grant a specific override
{
  "grant": ["project.edit"],
  "revoke": []
}

// Backend logic
$user = User::findOrFail($id);
foreach ($request->grant as $permission) {
    $user->givePermissionTo($permission);
}
foreach ($request->revoke as $permission) {
    $user->revokePermissionTo($permission);
}
```

### 1.4 Frontend Pinia Store: Effective Permissions Cache

```typescript
// store/usePermissionStore.ts
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { apiWithToken } from '@/services/http';

export const usePermissionStore = defineStore('permissions', () => {
  const rolePermissions = ref<string[]>([]);
  const directPermissions = ref<string[]>([]);
  const loaded = ref(false);

  // Computed: union of role + direct permissions
  const effectivePermissions = computed(() => {
    const set = new Set([...rolePermissions.value, ...directPermissions.value]);
    return Array.from(set);
  });

  // Check a single permission
  function can(permission: string): boolean {
    return effectivePermissions.value.includes(permission);
  }

  // Check multiple permissions (OR logic)
  function canAny(permissions: string[]): boolean {
    return permissions.some(p => effectivePermissions.value.includes(p));
  }

  // Check multiple permissions (AND logic)
  function canAll(permissions: string[]): boolean {
    return permissions.every(p => effectivePermissions.value.includes(p));
  }

  // Load from /api/auth/me on app init
  async function loadFromAuth(user: any): Promise<void> {
    rolePermissions.value = user.permissions || [];
    // Direct permissions come from a separate endpoint
    try {
      const { data } = await apiWithToken.get(`/admin/users/${user.id}/permissions`);
      directPermissions.value = data.items.direct_permissions;
    } catch {
      directPermissions.value = [];
    }
    loaded.value = true;
  }

  return { rolePermissions, directPermissions, effectivePermissions, loaded, can, canAny, canAll, loadFromAuth };
});
```

### 1.5 AuthController Enhancement: Return Effective Permissions

```php
// In AuthController@login and @me
'permissions' => $user->getAllPermissions()->pluck('name'),
// getAllPermissions() already merges role + direct permissions!
```

---

## 2. Frontend Field-Locking Scheme

### 2.1 Architecture: Context-Aware `canAction` Engine

```
┌─────────────────────────────────────────────────────────────────────┐
│                    FIELD-LOCKING ARCHITECTURE                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  PermissionStore (Pinia)                                            │
│  ┌──────────────────────────────────────────────────────┐          │
│  │ effectivePermissions: string[]                        │          │
│  │ can(perm): boolean                                    │          │
│  │ canAny(perms): boolean                                │          │
│  └──────────────────────┬───────────────────────────────┘          │
│                         │                                          │
│  ┌──────────────────────▼───────────────────────────────┐          │
│  │ canAction(permission, context?)                       │          │
│  │   - Checks effectivePermissions                       │          │
│  │   - Checks resource ownership (context.userId)        │          │
│  │   - Checks resource state (context.status)            │          │
│  │   - Returns boolean                                   │          │
│  └──────────────────────┬───────────────────────────────┘          │
│                         │                                          │
│  ┌──────────────────────▼───────────────────────────────┐          │
│  │ Vue Component (TaskForm.vue)                          │          │
│  │                                                       │          │
│  │  <VTextField                                          │          │
│  │    :disabled="!canAction('task.edit-content', ctx)"   │          │
│  │  />                                                   │          │
│  │                                                       │          │
│  │  <VSelect                                             │          │
│  │    :disabled="!canAction('task.edit-own-status', ctx)"│          │
│  │  />                                                   │          │
│  └───────────────────────────────────────────────────────┘          │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 The `canAction` Helper (Refactored)

```typescript
// helpers/canAction.ts
import { usePermissionStore } from '@/store/usePermissionStore';
import { useAuthStore } from '@/store/useAuthStore';

export interface CanActionContext {
  resourceOwnerId?: number;   // e.g., task.created_by, ticket.created_by
  assignedToId?: number;      // e.g., task.assigned_to
  uploadedById?: number;      // e.g., attachment.uploaded_by
  status?: string;            // e.g., 'open', 'done', 'closed'
  projectOwnerId?: number;    // e.g., project.owner_id
  projectRole?: string;       // e.g., 'manager', 'developer'
}

/**
 * Context-aware permission check for frontend field locking.
 *
 * Rules:
 * 1. Super-admin bypass: always returns true
 * 2. PM/Owner bypass: if user is project owner or manager, grant all
 * 3. Permission check: user must have the permission in effectivePermissions
 * 4. Ownership check: for "own" permissions, user must match resourceOwnerId or assignedToId
 * 5. State check: for tickets, client can only edit if status === 'open'
 * 6. Attachment ownership: for attachment.delete-own, user must match uploadedById
 */
export function canAction(permission: string, context?: CanActionContext): boolean {
  const permStore = usePermissionStore();
  const authStore = useAuthStore();

  // 1. No session
  if (!authStore.authUser) return false;

  const userId = authStore.authUser.id;

  // 2. Super-admin bypass
  if (authStore.isSuperAdmin) return true;

  // 3. PM/Owner bypass for project-level permissions
  if (context?.projectOwnerId === userId || context?.projectRole === 'manager') {
    // PMs can do anything within their project
    if (permStore.can(permission)) return true;
  }

  // 4. Permission check
  if (!permStore.can(permission)) return false;

  // 5. Ownership check for "own" permissions
  if (permission.endsWith('-own') || permission.endsWith('-own-fields') || permission.endsWith('-own-status')) {
    const isOwner = context?.resourceOwnerId === userId;
    const isAssigned = context?.assignedToId === userId;
    if (!isOwner && !isAssigned) return false;
  }

  // 6. Attachment ownership check
  if (permission === 'attachment.delete-own') {
    if (context?.uploadedById !== userId) return false;
  }

  // 7. State check: client can only edit open tickets
  if (permission.startsWith('ticket.edit-own') && context?.projectRole === 'client') {
    if (context?.status !== 'open') return false;
  }

  // 8. State check: no one can edit completed tasks
  if (permission.startsWith('task.edit') && context?.status === 'done') {
    return false;
  }

  // 9. State check: no one can edit closed tickets
  if (permission.startsWith('ticket.edit') && context?.status === 'closed') {
    return false;
  }

  return true;
}
```

### 2.3 Vue 3 Component Integration: TaskForm.vue (Refactored)

```vue
<script setup lang="ts">
import { computed } from 'vue';
import { canAction } from '@/helpers/canAction';
import { useAuthStore } from '@/store/useAuthStore';
import type { TaskI } from '@/interfaces/TaskI';

const props = defineProps<{
  form: TaskI;
  task?: TaskI; // existing task for edit mode
  projectOwnerId: number;
  projectRole: string;
}>();

const authStore = useAuthStore();

// Build context from the task being edited
const ctx = computed(() => ({
  resourceOwnerId: props.task?.created_by,
  assignedToId: props.task?.assigned_to,
  status: props.task?.status,
  projectOwnerId: props.projectOwnerId,
  projectRole: props.projectRole,
}));

// Computed field locks
const canEditContent = computed(() => canAction('task.edit-content', ctx.value));
const canEditStatus = computed(() => canAction('task.edit-own-status', ctx.value));
const canEditFields = computed(() => canAction('task.edit-own-fields', ctx.value));
const canManageAttachments = computed(() => canAction('task.manage-attachments', ctx.value));
</script>

<template>
  <VCard>
    <VCardText>
      <VRow>
        <!-- Title: only PM can edit -->
        <VCol cols="12" md="8">
          <VTextField
            v-model="form.title"
            label="Título"
            :disabled="!canEditContent"
            :readonly="!canEditContent"
          />
        </VCol>

        <!-- Status: anyone with edit-own-status can change -->
        <VCol cols="12" md="4">
          <VSelect
            v-model="form.status"
            label="Estado"
            :items="statuses"
            :disabled="!canEditStatus"
          />
        </VCol>

        <!-- Description: only PM can edit -->
        <VCol cols="12">
          <VTextarea
            v-model="form.description"
            label="Descripción"
            :disabled="!canEditContent"
            :readonly="!canEditContent"
          />
        </VCol>

        <!-- Priority: only PM or if user has edit-own-fields -->
        <VCol cols="12" md="4">
          <VSelect
            v-model="form.priority"
            label="Prioridad"
            :items="priorities"
            :disabled="!canEditFields"
          />
        </VCol>

        <!-- Progress: anyone with edit-own-fields -->
        <VCol cols="12" md="4">
          <VTextField
            v-model="form.progress"
            label="Progreso (%)"
            type="number"
            :disabled="!canEditFields"
          />
        </VCol>

        <!-- Attachments: only PM can manage -->
        <VCol cols="12">
          <VFileInput
            label="Archivos adjuntos"
            multiple
            :disabled="!canManageAttachments"
          />
        </VCol>
      </VRow>
    </VCardText>
  </VCard>
</template>
```

### 2.4 Custom Vue Directive: `v-can-action`

```typescript
// directives/canAction.ts
import { type Directive } from 'vue';
import { canAction, type CanActionContext } from '@/helpers/canAction';

export const vCanAction: Directive<HTMLElement, {
  permission: string;
  context?: CanActionContext;
  attribute?: 'disabled' | 'readonly' | 'hidden';
}> = {
  mounted(el, binding) {
    const { permission, context, attribute = 'disabled' } = binding.value;
    const allowed = canAction(permission, context);

    if (!allowed) {
      if (attribute === 'hidden') {
        el.style.display = 'none';
      } else {
        el.setAttribute(attribute, 'true');
        el.classList.add('v-input--disabled');
      }
    }
  },

  updated(el, binding) {
    const { permission, context, attribute = 'disabled' } = binding.value;
    const allowed = canAction(permission, context);

    if (!allowed) {
      if (attribute === 'hidden') {
        el.style.display = 'none';
      } else {
        el.setAttribute(attribute, 'true');
        el.classList.add('v-input--disabled');
      }
    } else {
      if (attribute === 'hidden') {
        el.style.display = '';
      } else {
        el.removeAttribute(attribute);
        el.classList.remove('v-input--disabled');
      }
    }
  },
};
```

**Usage in templates:**

```vue
<template>
  <VTextField
    v-model="form.title"
    label="Título"
    v-can-action="{
      permission: 'task.edit-content',
      context: ctx,
      attribute: 'disabled'
    }"
  />
</template>
```

---

## 3. Secure Async Attachment Lifecycle

### 3.1 Complete Lifecycle State Machine

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ATTACHMENT STATE MACHINE                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────┐    upload files     ┌─────────────┐    form submit    ┌──────────┐
│  │  DRAFT   │ ──────────────────▶ │  TEMPORARY   │ ────────────────▶ │ PERMANENT│
│  │ (no files│   POST /attachments │ (draft_token)│   POST /tasks     │ (linked) │
│  │  yet)    │   /temporary        │              │   {draft_token}   │          │
│  └──────────┘                     └──────┬───────┘                   └──────────┘
│                                          │                                 ▲
│                                          │ user cancels                    │
│                                          ▼                                 │
│                                     ┌──────────┐                          │
│                                     │  ORPHAN  │   cron job (24h)          │
│                                     │ (abandon)│ ──────────────────────────┘
│                                     └──────────┘   DELETE
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Backend Implementation

#### Migration: Make attachments table support draft state

```php
// database/migrations/xxxx_xx_xx_add_draft_support_to_attachments.php
Schema::table('attachments', function (Blueprint $table) {
    // Make polymorphic columns nullable for draft state
    $table->unsignedBigInteger('attachable_id')->nullable()->change();
    $table->string('attachable_type')->nullable()->change();

    // New columns
    $table->string('draft_token')->nullable()->after('uuid')->index();
    $table->string('status')->default('permanent')->after('draft_token');
    // status: 'temporary' | 'permanent'
});
```

#### DraftSessionController

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DraftSessionController extends Controller
{
    /**
     * POST /api/v1/draft-sessions
     *
     * Generates a unique draft token for a new resource form.
     * The token is not persisted — it's returned to the frontend
     * and used as a grouping key for temporary attachments.
     */
    public function store(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data'   => [
                'draft_token' => (string) Str::uuid(),
            ],
            'message' => 'Draft session created.',
        ]);
    }

    /**
     * DELETE /api/v1/draft-sessions/{token}
     *
     * Called when the user cancels the form.
     * Deletes all temporary attachments associated with this token.
     */
    public function destroy(string $token): JsonResponse
    {
        $attachments = \App\Models\Attachment::where('draft_token', $token)
            ->where('status', 'temporary')
            ->where('uploaded_by', auth()->id())
            ->get();

        foreach ($attachments as $attachment) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($attachment->disk_path);
            $attachment->delete();
        }

        return response()->json([
            'status'  => true,
            'message' => count($attachments) . ' temporary attachment(s) deleted.',
        ]);
    }
}
```

#### AttachmentController: uploadTemporary

```php
/**
 * POST /api/v1/attachments/temporary
 *
 * Uploads files before the parent record exists.
 * Files are stored with status='temporary' and grouped by draft_token.
 */
public function uploadTemporary(Request $request): JsonResponse
{
    $request->validate([
        'draft_token' => ['required', 'string', 'uuid'],
        'attachments'   => ['required', 'array', 'max:10'],
        'attachments.*' => ['file', 'max:102400'], // 100MB
    ]);

    $this->authorize('uploadTemporary', Attachment::class);

    try {
        $files = $request->file('attachments');
        $draftToken = $request->input('draft_token');
        $uploaded = [];

        foreach ($files as $file) {
            $attachmentUuid = (string) Str::uuid();
            $extension = $file->getClientOriginalExtension();

            // Store in a temporary draft directory
            $diskPath = sprintf('drafts/%s/%s.%s', $draftToken, $attachmentUuid, $extension);

            Storage::disk('local')->makeDirectory(dirname($diskPath));
            Storage::disk('local')->putFileAs(dirname($diskPath), $file, basename($diskPath));

            $attachment = Attachment::create([
                'uuid'           => $attachmentUuid,
                'draft_token'    => $draftToken,
                'status'         => 'temporary',
                'original_name'  => $file->getClientOriginalName(),
                'disk_path'      => $diskPath,
                'mime_type'      => $file->getMimeType(),
                'size'           => $file->getSize(),
                'uploaded_by'    => $request->user()->id,
            ]);

            $uploaded[] = $attachment;
        }

        return response()->json([
            'status' => true,
            'data'   => $uploaded,
            'message' => count($uploaded) . ' archivo(s) subido(s) temporalmente.',
        ], 201);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => $th->getMessage(),
        ], 500);
    }
}
```

#### AttachmentController: claim (called after parent is created)

```php
/**
 * POST /api/v1/attachments/claim
 *
 * Links temporary attachments to a newly created parent record.
 * Only PMs can claim (attachment.claim permission).
 */
public function claim(Request $request): JsonResponse
{
    $request->validate([
        'draft_token'  => ['required', 'string', 'uuid'],
        'attachable_type' => ['required', 'string'],
        'attachable_id'   => ['required', 'integer'],
    ]);

    $this->authorize('claim', Attachment::class);

    try {
        $parentClass = $request->input('attachable_type');
        $parentId = $request->input('attachable_id');
        $parent = $parentClass::findOrFail($parentId);

        // Move files from draft directory to project directory
        $attachments = Attachment::where('draft_token', $request->draft_token)
            ->where('status', 'temporary')
            ->where('uploaded_by', $request->user()->id)
            ->get();

        foreach ($attachments as $attachment) {
            // Move file from drafts/{token}/ to projects/{project_uuid}/
            $projectUuid = $this->attachmentService->resolveProjectUuid($parent);
            $newDiskPath = str_replace(
                'drafts/' . $request->draft_token,
                'projects/' . $projectUuid,
                $attachment->disk_path
            );

            Storage::disk('local')->move($attachment->disk_path, $newDiskPath);

            $attachment->update([
                'attachable_type' => $parentClass,
                'attachable_id'   => $parentId,
                'disk_path'       => $newDiskPath,
                'draft_token'     => null,
                'status'          => 'permanent',
            ]);
        }

        // Fire domain event
        event(new \App\Events\AttachmentsClaimed(
            parent: $parent,
            attachmentUuids: $attachments->pluck('uuid')->toArray(),
            claimedBy: $request->user(),
        ));

        return response()->json([
            'status' => true,
            'data'   => $attachments->fresh(),
            'message' => count($attachments) . ' archivo(s) vinculado(s) permanentemente.',
        ]);
    } catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' => $th->getMessage(),
        ], 500);
    }
}
```

#### AttachmentPolicy (New)

```php
<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    /**
     * Any authenticated user can upload temporary files.
     */
    public function uploadTemporary(User $user): bool
    {
        return $user->can('attachment.upload-temporary');
    }

    /**
     * Only PMs can claim temporary attachments to a parent.
     */
    public function claim(User $user): bool
    {
        return $user->can('attachment.claim');
    }

    /**
     * Delete own temporary attachments.
     */
    public function deleteOwn(User $user): bool
    {
        return $user->can('attachment.delete-own');
    }

    /**
     * Delete any attachment (PM only).
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('attachment.delete-any');
    }

    /**
     * Instance-level delete: checks ownership for non-PM users.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        // PM can delete any attachment
        if ($user->can('attachment.delete-any')) {
            return true;
        }

        // Regular users can only delete their own temporary attachments
        return $user->can('attachment.delete-own')
            && $attachment->uploaded_by === $user->id
            && $attachment->status === 'temporary';
    }
}
```

### 3.3 Frontend Integration: Draft Token Flow

```typescript
// composables/useDraftSession.ts
import { ref, onUnmounted } from 'vue';
import { apiWithToken } from '@/services/http';
import { useAttachments } from '@/composables/useAttachments';

export function useDraftSession() {
  const draftToken = ref<string | null>(null);
  const temporaryAttachments = ref<any[]>([]);
  const { upload, remove, uploading } = useAttachments();

  /**
   * Initialize a draft session when the form mounts.
   */
  async function initDraftSession(): Promise<void> {
    try {
      const { data } = await apiWithToken.post('/draft-sessions');
      draftToken.value = data.data.draft_token;
    } catch {
      console.warn('Failed to create draft session');
    }
  }

  /**
   * Upload files to the draft session (before parent exists).
   */
  async function uploadTemporary(files: File[]): Promise<void> {
    if (!draftToken.value) return;

    const fd = new FormData();
    fd.append('draft_token', draftToken.value);
    files.forEach(f => fd.append('attachments[]', f));

    try {
      const { data } = await apiWithToken.post('/attachments/temporary', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      temporaryAttachments.value.push(...data.data);
    } catch (e) {
      console.error('Failed to upload temporary files', e);
    }
  }

  /**
   * Claim temporary attachments to the newly created parent.
   */
  async function claimAttachments(attachableType: string, attachableId: number): Promise<void> {
    if (!draftToken.value || temporaryAttachments.value.length === 0) return;

    try {
      await apiWithToken.post('/attachments/claim', {
        draft_token: draftToken.value,
        attachable_type: attachableType,
        attachable_id: attachableId,
      });
      temporaryAttachments.value = [];
    } catch (e) {
      console.error('Failed to claim attachments', e);
    }
  }

  /**
   * Cleanup on cancel or component unmount.
   */
  async function cleanup(): Promise<void> {
    if (!draftToken.value) return;
    try {
      await apiWithToken.delete(`/draft-sessions/${draftToken.value}`);
    } catch {
      // Ignore cleanup errors
    }
    draftToken.value = null;
    temporaryAttachments.value = [];
  }

  // Auto-cleanup on unmount
  onUnmounted(() => {
    if (draftToken.value) {
      cleanup();
    }
  });

  return {
    draftToken,
    temporaryAttachments,
    uploading,
    initDraftSession,
    uploadTemporary,
    claimAttachments,
    cleanup,
  };
}
```

### 3.4 Garbage Collection

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

## 4. Laravel Policy Blueprints

### 4.1 TaskPolicy (Full Implementation)

```php
<?php

namespace App\Policies;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('task.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.view');
    }

    public function create(User $user): bool
    {
        return $user->can('task.create');
    }

    /**
     * Multi-tiered update policy:
     *
     * - PM/Owner: can edit ALL fields (task.edit-content)
     * - Developer/QA (assigned): can edit ONLY status, progress, worked_hours (task.edit-own-fields)
     * - No one can edit a completed task
     * - Field-level enforcement happens in UpdateTaskRequest (FormRequest)
     */
    public function update(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        // PM / Owner: full content edit
        if ($task->project->owner_id === $user->id || $user->hasProjectRole($task->project, 'manager')) {
            return $user->canForProject($task->project, 'task.edit-content');
        }

        // Developer / QA: field-restricted edit (only own tasks)
        return $user->canForProject($task->project, 'task.edit-own-fields')
            && $task->assigned_to === $user->id;
    }

    /**
     * Status transitions: separate from content edit.
     * The assigned user, PM, and owner can change status.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        return $user->canForProject($task->project, 'task.edit-own-status')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager')
                || $task->assigned_to === $user->id);
    }

    public function assign(User $user): bool
    {
        return $user->can('task.assign');
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.delete')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager'));
    }

    public function logTime(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.edit-own-time')
            && $task->assigned_to === $user->id;
    }

    /**
     * Attachment management: only PM/owner.
     */
    public function manageAttachments(User $user, Task $task): bool
    {
        return $user->canForProject($task->project, 'task.manage-attachments')
            && ($task->project->owner_id === $user->id
                || $user->hasProjectRole($task->project, 'manager'));
    }
}
```

### 4.2 UpdateTaskRequest (Field-Level Mass-Assignment Protection)

```php
<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by TaskPolicy@update
    }

    public function rules(): array
    {
        $user = $this->user();
        $task = $this->route('task');

        $rules = [];

        // PM / Owner: can edit everything
        if ($task->project->owner_id === $user->id || $user->hasProjectRole($task->project, 'manager')) {
            $rules = [
                'title'           => ['sometimes', 'string', 'max:255'],
                'description'     => ['sometimes', 'nullable', 'string'],
                'priority'        => ['sometimes', 'nullable', 'string', 'in:low,medium,high,critical'],
                'estimated_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'assigned_to'     => ['sometimes', 'nullable', 'exists:users,id'],
                'due_date'        => ['sometimes', 'nullable', 'date'],
                'status'          => ['sometimes', 'string', 'in:pending,in_progress,review,blocked,done'],
                'progress'        => ['sometimes', 'integer', 'min:0', 'max:100'],
                'worked_hours'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            ];
        }
        // Developer / QA (assigned): only status, progress, worked_hours
        elseif ($task->assigned_to === $user->id) {
            $rules = [
                'status'       => ['sometimes', 'string', 'in:in_progress,review,done'],
                'progress'     => ['sometimes', 'integer', 'min:0', 'max:100'],
                'worked_hours' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            ];
        }

        return $rules;
    }
}
```

### 4.3 TicketPolicy (Full Implementation)

```php
<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ticket.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.view');
    }

    public function create(User $user): bool
    {
        return $user->can('ticket.create');
    }

    /**
     * Multi-tiered update policy:
     *
     * - PM/Owner: can edit ALL fields (ticket.edit-any-fields)
     * - Client: can edit ONLY own tickets, ONLY if status is 'open' (ticket.edit-own-fields)
     * - Developer/QA/Support: can edit own tickets (ticket.edit-own-fields)
     * - No one can edit a closed ticket
     * - Field-level enforcement happens in UpdateTicketRequest (FormRequest)
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status->isClosed()) {
            return false;
        }

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

    public function assign(User $user): bool
    {
        return $user->can('ticket.assign');
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.delete')
            && ($ticket->project->owner_id === $user->id
                || $user->hasProjectRole($ticket->project, 'manager'));
    }

    public function manageAttachments(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.manage-attachments')
            && ($ticket->project->owner_id === $user->id
                || $user->hasProjectRole($ticket->project, 'manager'));
    }
}
```

### 4.4 UpdateTicketRequest (Field-Level Mass-Assignment Protection)

```php
<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by TicketPolicy@update
    }

    public function rules(): array
    {
        $user = $this->user();
        $ticket = $this->route('ticket');

        $rules = [];

        // PM / Owner: can edit everything
        if ($ticket->project->owner_id === $user->id || $user->hasProjectRole($ticket->project, 'manager')) {
            $rules = [
                'subject'     => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string'],
                'priority'    => ['sometimes', 'string', 'in:low,medium,high,critical'],
                'status'      => ['sometimes', 'string', 'in:open,in_progress,resolved,closed'],
                'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            ];
        }
        // Client / Developer / QA / Support: only subject, description, status
        elseif ($ticket->created_by === $user->id) {
            $rules = [
                'subject'     => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string'],
                'status'      => ['sometimes', 'string', 'in:open,in_progress,resolved'],
            ];
        }

        return $rules;
    }
}
```

---

## 5. Refactored RolesAndPermissionsSeeder

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
