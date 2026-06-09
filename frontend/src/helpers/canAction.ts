import { getAuthToken } from '@/services/http';
import { usePermissionStore } from '@/store/usePermissionStore';
import { useAuthStore } from '@/store/useAuthStore';

/**
 * Minimal authorization check on the frontend.
 *
 * The backend is the single source of truth. This helper only verifies:
 * 1. An auth token exists.
 * 2. The named permission is present in the PermissionStore.
 * 3. If the permission ends with "-own", the user must be the owner of the resource.
 *
 * All state/role-based business rules are enforced by Laravel policies;
 * field-level locking is driven by `field_permissions` returned by the API.
 */
export function canAction(action: string, resourceOwnerId?: number | null): boolean {
  if (!getAuthToken()) return false;

  const permissionStore = usePermissionStore();

  if (!permissionStore.loaded) {
    // Permissions not yet loaded — be permissive only if we have a valid token.
    // Once the store hydrates, the UI will reactively lock itself.
    return true;
  }

  if (!permissionStore.hasPermission(action)) {
    return false;
  }

  // "-own" actions require the resource to belong to the current user.
  if (action.endsWith('-own') && resourceOwnerId !== undefined && resourceOwnerId !== null) {
    const authStore = useAuthStore();
    return authStore.authUser?.id === resourceOwnerId;
  }

  return true;
}
