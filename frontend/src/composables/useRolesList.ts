// composables/useRoles.ts
import { ref } from 'vue';
import { index, type RoleI } from '../services/roles.service';

export function useRoles() {
  const roles = ref<RoleI[]>([]);
  const loading = ref(false);
  const errorMessage = ref('');

  const loadRoles = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
      const response = await index();
      if (response.status && response.items) {
        roles.value = response.items;
      } else {
        errorMessage.value = response.message || 'Error al cargar roles';
      }
    } catch (error) {
      errorMessage.value = 'Error en el servidor';
      console.error(error);
    } finally {
      loading.value = false;
    }
  };

  return {
    roles,
    loading,
    errorMessage,
    loadRoles,
  };
}
