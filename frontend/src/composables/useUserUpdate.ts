import { ref } from 'vue';
import { useUsersService } from './index';
import { useUserForm } from './useUserForm';

import { apiWithToken } from '@/services/http';

export function useUserUpdate() {
    const usersService = useUsersService();
    const { form, errors, clearLocalErrors, setBackendErrors, setLocalError } = useUserForm();
    const isLoading = ref(false);
    const successMessage = ref('');

    // Lista de todos los permisos disponibles cargados del backend
    const availablePermissions = ref<{ id: number; name: string }[]>([]);

    /** Permisos que vienen del rol (no asignados directamente). */
    const rolePermissions = ref<Set<string>>(new Set());

    async function fetchPermissions(): Promise<void> {
        try {
            const { data } = await apiWithToken.get<{ status: boolean; items: { id: number; name: string }[] }>('/permissions');
            if (data.status && Array.isArray(data.items)) {
                availablePermissions.value = data.items;
            }
        } catch {
            // Silencioso si falla; el super-admin igual puede guardar
        }
    }

    function togglePermission(permName: string): void {
        const idx = form.permissions.indexOf(permName);
        if (idx === -1) {
            form.permissions.push(permName);
        } else {
            form.permissions.splice(idx, 1);
        }
    }

    function validatePasswordIfProvided(): boolean {
        if (!form.password) {
            return true; // Password es opcional en update
        }

        clearLocalErrors();

        if (form.password.length < 8) {
            setLocalError('password', 'El password debe tener al menos 8 caracteres.');
        }

        if (form.password_confirmation && form.password !== form.password_confirmation) {
            setLocalError('password_confirmation', 'La confirmacion de password no coincide.');
        }

        // Return true if valid (no local errors)
        const hasLocalErrors = Object.values(errors).some((errs) => Array.isArray(errs) && errs.length > 0);
        return !hasLocalErrors;
    }

    async function handleUpdate(userId: number): Promise<boolean> {
        successMessage.value = '';

        if (!validatePasswordIfProvided()) {
            return false;
        }

        isLoading.value = true;

        const response = await usersService.call('update', userId, {
            name: form.name.trim(),
            email: form.email.trim(),
            role: form.role || null,
            permissions: form.permissions,
            password: form.password || undefined,
        });

        isLoading.value = false;

        if (response) {
            successMessage.value = `Usuario actualizado correctamente.`;
            return true;
        } else {
            setBackendErrors(usersService.validationErrors.value);
            return false;
        }
    }

    async function loadUser(userId: number): Promise<boolean> {
        isLoading.value = true;

        const response = await usersService.call('show', userId);

        isLoading.value = false;

        if (response) {
            form.name = response.items?.name || '';
            form.email = response.items?.email || '';
            form.role = response.items?.roles?.[0] || '';
            form.permissions = response.items?.permissions || [];
            // Distinguir permisos del rol vs directos
            rolePermissions.value = new Set(response.items?.role_permissions ?? []);
            form.password = '';
            form.password_confirmation = '';
            return true;
        }

        return false;
    }

    return {
        form,
        errors,
        isLoading,
        successMessage,
        usersService,
        availablePermissions,
        rolePermissions,
        handleUpdate,
        loadUser,
        fetchPermissions,
        togglePermission,
    };
}
