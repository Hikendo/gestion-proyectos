import { ref, computed } from 'vue';
import { useRolesService } from './index';

export interface RoleOption {
    name: string;
    label: string;
}

export function useRolesList() {
    const rolesService = useRolesService();
    const isLoading = ref(false);
    const roles = ref<RoleOption[]>([]);

    async function loadRoles(): Promise<boolean> {
        isLoading.value = true;

        const response = await rolesService.call('list');

        isLoading.value = false;

        if (response) {
            roles.value = Array.isArray(response) ? response : [];
            return true;
        }

        return false;
    }

    const hasRoles = computed(() => roles.value.length > 0);

    return {
        roles,
        isLoading,
        hasRoles,
        rolesService,
        loadRoles,
    };
}
