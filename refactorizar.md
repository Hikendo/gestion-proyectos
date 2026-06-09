# Context & Refactoring Task: Hybrid RBAC+ABAC, Field-Locking & Async Attachments

You are an expert developer in **Laravel 12, Vue 3 (Composition API, Pinia, Vuetify 3), Firebase Cloud Messaging**, and decoupled project management systems.  
The application uses a hybrid RBAC+ABAC permissions model with Spatie Permission, field‑locking on the frontend, and an asynchronous attachment lifecycle.

The codebase follows strict **Development Standards** (simplicity, reuse, centralised authorisation, no duplication).  
**Node modules and vendor directories must be ignored.**

Your task is to refactor the weak points identified in a previous audit, **maintaining full coherence** with the existing codebase, **not breaking any existing tests**, and only creating/rewriting tests when strictly necessary (a test that becomes incoherent with the business logic must be updated, not removed without replacement).

---

## 🔴 Weak points to resolve (prioritised)

### 1. Implement project‑scoped authorisation (`canForProject` / `hasProjectRole`)

- **Problem:** Policies call `$user->canForProject($project, 'permission')` and `$user->hasProjectRole($project, 'role')`, but these methods do **not exist** on the `User` model. Without them, a user with a global permission can access resources from any project.
- **What to do:**
  - Create the necessary pivot table (e.g. `project_user`) if not present, with `user_id`, `project_id`, `role` (string).
  - Implement the methods on `User` (and `Project` if needed) using the existing `ProjectMember` model or a dedicated Trait (e.g. `HasProjectAccess`). You may leverage Spatie’s team support if feasible.
  - All authorisation checks that involve a project must be scoped to that project.
  - **Create/update tests** that verify cross‑project access is blocked and roles within a project are correctly evaluated.

### 2. Eliminate duplicated authorisation logic between frontend and backend

- **Problem:** The frontend helper `canAction.ts` replicates business rules (state checks for `done`/`closed`, ownership, project role) already enforced by Laravel policies.
- **What to do:**
  - Make the backend the **single source of truth**. When returning a resource (task, ticket, etc.), include a `field_permissions` object with a boolean per editable field (e.g. `{ title: true, description: false, status: true }`).
  - Modify API controllers/resources to compute and include `field_permissions`.
  - Adapt form components (`TaskForm.vue`, `TicketForm.vue`, etc.) to simply read `field_permissions` instead of calling `canAction` with business rules.
  - `canAction.ts` should be reduced to a minimal check: verify the permission exists in the store and, if the permission name contains `-own`, check that the resource belongs to the current user. Remove all state/role‑based rules from it.
  - **Update frontend tests** (`canAction.spec.ts`) to reflect the removal of business logic, and add backend tests to ensure `field_permissions` is calculated correctly.

### 3. Refactor `v-can-action` directive to be reactive

- **Problem:** The current directive does not react to deep context changes (e.g. status changing from `open` to `closed`) because Vue’s directive `updated` hook only triggers when the binding value reference changes, not its internal properties.
- **What to do:**
  - Replace the directive with a **composable** (e.g. `useFieldLock(fieldName, context)`) that returns a `computed<boolean>` and is used in templates like `:disabled="!canEditTitle"`.
  - Update all templates that previously used `v-can-action` to use the composable.
  - The new implementation must be fully reactive and follow Vue 3 Composition API best practices.

### 4. Real‑time permissions cache invalidation via FCM

- **Problem:** When an admin updates roles or permissions (`PUT /api/admin/roles/{role}`, `PUT /api/admin/users/{user}/permissions`), the change does not affect active user sessions because Spatie caches permissions.
- **What to do:**
  - After any role/permission change, send a silent FCM push notification with data payload `{ type: 'permissions_updated' }` to the affected user(s).
  - Create an endpoint `POST /api/auth/refresh-permissions` that returns the fresh set of permissions for the authenticated user.
  - In the frontend, listen for this FCM message (in `App.vue` or the notification store) and, upon receiving it, call the refresh endpoint and update the `PermissionStore`.
  - Ensure the backend clears Spatie’s cache appropriately after modifying roles/permissions.

### 5. Robust attachment file moving during claim

- **Problem:** The `claim` method in `AttachmentController` (or service) uses `str_replace('drafts/...', 'projects/...', $disk_path)`, which is fragile and may break if the directory structure changes or the token appears elsewhere in the path.
- **What to do:**
  - Refactor the moving logic to use a safer approach: store only the relative filename and reconstruct the full path using configurable base directories (e.g. `config('filesystems.disks.local.draft_root')` and `project_root`), or use `Str::after`/`Str::afterLast` to extract the relevant part.
  - Verify that the destination directory exists, creating it if necessary.
  - Wrap the status update and file move in a database transaction if possible.
  - **Create an integration test** that simulates the full lifecycle (upload temporary, create parent, claim) and verifies files are moved correctly.

---

## ⚙️ Constraints & Standards

- **Follow the project’s Development Standards:** keep it simple, avoid duplication, centralise authorisation, reuse existing services/traits/models, and place business logic in the correct layer.
- **Do not introduce new abstractions or patterns** unless they solve a concrete problem that cannot be addressed by extending the current code.
- **Coherence is mandatory.** All existing tests (backend `tests/` and frontend `__tests__/`) must pass after the refactoring.
- **Tests:**  
  - If an existing test becomes incoherent due to your changes, **update it** to validate the correct business logic.  
  - Only **create new tests** when they are strictly necessary to cover the new functionality (e.g. project‑scoped authorisation, `field_permissions` computation, refresh‑permissions endpoint, attachment claim).  
  - Never delete a test without providing a replacement that reflects the intended behaviour.
- **Ignore `node_modules` and `vendor` directories.**

---

## 📦 Expected Deliverables

- Refactored backend and frontend code according to the points above.
- Any required migrations.
- New/modified endpoints.
- Updated/created tests.
- A brief summary of changes and confirmation that existing tests still pass (or which tests were updated and why).

Begin by inspecting the relevant parts of the codebase and then implement the changes, ensuring full alignment with the existing project structure.
