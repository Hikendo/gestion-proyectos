import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useAuthStore } from '@/store/useAuthStore';
import { apiWithToken } from '@/services/http';

export interface FieldPermissions {
  [field: string]: boolean;
}

export const usePermissionStore = defineStore('permissions', () => {
  const permissions = ref<string[]>([]);
  const loaded = ref(false);

  const hasPermission = computed(() => (name: string) => permissions.value.includes(name));

  /**
   * Initialize permissions from the auth user object (during login/me).
   */
  function setPermissions(perms: string[]) {
    permissions.value = [...perms];
    loaded.value = true;
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
    loaded.value = false;
  }

  return {
    permissions,
    loaded,
    hasPermission,
    setPermissions,
    refreshPermissions,
    clearPermissions,
  };
});