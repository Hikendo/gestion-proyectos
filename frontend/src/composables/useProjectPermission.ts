import { ref, type Ref, watch } from 'vue';
import { apiWithToken } from '@/services/http';

export interface ProjectPermissions {
  can_view: boolean;
  can_edit: boolean;
  can_delete: boolean;
  can_assign_members: boolean;
  can_manage_attachments: boolean;
  is_owner: boolean;
  project_role: string | null;
}

/**
 * Composable que consulta los permisos del usuario autenticado
 * sobre un proyecto específico.
 *
 * @param projectId - Ref al ID del proyecto (puede ser null).
 * @returns { canEdit, canDelete, canAssignMembers, permissions, loading, error }
 *
 * @example
 * ```ts
 * const projectId = ref(1);
 * const { canEdit, loading } = useProjectPermission(projectId);
 * // canEdit.value === true si el usuario puede editar el proyecto
 * ```
 */
export function useProjectPermission(projectId: Ref<number | null>) {
  const permissions = ref<ProjectPermissions | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Derived booleans for convenience
  const canEdit = ref(false);
  const canDelete = ref(false);
  const canAssignMembers = ref(false);
  const canManageAttachments = ref(false);
  const isOwner = ref(false);
  const projectRole = ref<string | null>(null);

  async function fetchPermissions(id: number): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const { data } = await apiWithToken.get<{
        status: boolean;
        items: ProjectPermissions;
        message: string;
      }>(`/projects/${encodeURIComponent(id)}/permissions`);

      if (data.status && data.items) {
        permissions.value = data.items;
        canEdit.value = data.items.can_edit;
        canDelete.value = data.items.can_delete;
        canAssignMembers.value = data.items.can_assign_members;
        canManageAttachments.value = data.items.can_manage_attachments;
        isOwner.value = data.items.is_owner;
        projectRole.value = data.items.project_role;
      } else {
        // El backend devolvió no autorizado u otro error
        permissions.value = null;
        canEdit.value = false;
        canDelete.value = false;
        canAssignMembers.value = false;
        canManageAttachments.value = false;
        isOwner.value = false;
        projectRole.value = null;
      }
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? err?.message ?? 'Error al cargar permisos';
      permissions.value = null;
      canEdit.value = false;
      canDelete.value = false;
      canAssignMembers.value = false;
      canManageAttachments.value = false;
      isOwner.value = false;
      projectRole.value = null;
    } finally {
      loading.value = false;
    }
  }

  // React to projectId changes
  watch(
    () => projectId.value,
    (id) => {
      if (id !== null && id !== undefined && Number.isFinite(id)) {
        fetchPermissions(id);
      } else {
        permissions.value = null;
        canEdit.value = false;
        canDelete.value = false;
        canAssignMembers.value = false;
        canManageAttachments.value = false;
        isOwner.value = false;
        projectRole.value = null;
        error.value = null;
        loading.value = false;
      }
    },
    { immediate: true },
  );

  return {
    permissions,
    canEdit,
    canDelete,
    canAssignMembers,
    canManageAttachments,
    isOwner,
    projectRole,
    loading,
    error,
    refresh: (id?: number) => fetchPermissions(id ?? projectId.value as number),
  };
}