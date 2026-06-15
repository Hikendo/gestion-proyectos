import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from '@/store/useAuthStore';
import { apiWithToken } from '@/services/http';

export interface FieldPermissions {
  [field: string]: boolean;
}

export const usePermissionStore = defineStore('permissions', () => {
  const permissions = ref<string[]>([]);
  /** Permisos específicos del proyecto actual (ProjectMemberRole::permissionsFor) */
  const projectPermissions = ref<string[]>([]);
  const loaded = ref(false);

  const hasPermission = computed(() => (name: string) => {
    return permissions.value.includes(name) || projectPermissions.value.includes(name);
  });

  /**
   * Initialize permissions from the auth user object (during login/me).
   */
  function setPermissions(perms: string[]) {
    permissions.value = [...perms];
    loaded.value = true;
  }

  /**
   * Inyecta los permisos planos del proyecto actual.
   * Se llama desde useEnsureCurrentProject o router.beforeEach.
   */
  function setProjectPermissions(perms: string[]) {
    projectPermissions.value = [...perms];
  }

  /**
   * Limpia los permisos del proyecto al salir del mismo.
   */
  function clearProjectPermissions() {
    projectPermissions.value = [];
  }

  /**
   * Call the backend to refresh permissions (after FCM invalidation).
   */
  async function refreshPermissions(): Promise<void> {
    try {
      const response = await apiWithToken.post('/auth/refresh-permissions');
      if (response.data?.status && Array.isArray(response.data?.items)) {
        permissions.value = response.data.items;
        loaded.value = true;
      }
    } catch (error) {
      console.error('Error refreshing permissions:', error);
    }
  }

  /**
   * Clears all stored permissions (on logout).
   */
  function clearPermissions() {
    permissions.value = [];
    projectPermissions.value = [];
    loaded.value = false;
  }

  return {
    permissions,
    projectPermissions,
    loaded,
    hasPermission,
    setPermissions,
    setProjectPermissions,
    clearProjectPermissions,
    refreshPermissions,
    clearPermissions,
  };
});
