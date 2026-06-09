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
export function canAction(
  action: string | string[],
  resourceOwnerId?: number | null,
): boolean {
  if (!getAuthToken()) return false;

  const permissionStore = usePermissionStore();

  if (!permissionStore.loaded) {
    return true; // permisos aún no hidratados → permissive
  }

  const actions = Array.isArray(action) ? action : [action];

  // ¿Tiene al menos un permiso no-own de la lista?
  const hasNonOwn = actions.some(a => !a.endsWith('-own') && permissionStore.hasPermission(a));
  if (hasNonOwn) return true;

  // ¿Tiene un permiso -own y es el dueño del recurso?
  if (resourceOwnerId !== undefined && resourceOwnerId !== null) {
    const authStore = useAuthStore();
    return actions.some(
      a => a.endsWith('-own') && permissionStore.hasPermission(a),
    ) && authStore.authUser?.id === resourceOwnerId;
  }

  // Solo permiso -own sin resourceOwnerId → no mostramos (contexto de lista)
  return false;
}
