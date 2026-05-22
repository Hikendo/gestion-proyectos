import { ref } from 'vue';
import { useUsersService } from './index';
import { useUserForm } from './useUserForm';

export function useUserUpdate() {
    const usersService = useUsersService();
    const { form, errors, clearLocalErrors, setBackendErrors, setLocalError } = useUserForm();
    const isLoading = ref(false);
    const successMessage = ref('');

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
        const hasLocalErrors = Object.values(errors).some((errs) => errs && errs.length > 0);
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
            role: form.role,
            password: form.password || undefined,
        });

        isLoading.value = false;

        if (response) {
            successMessage.value = `Usuario actualizado correctamente.`;
            return true;
        } else {
            setBackendErrors(usersService.validationErrors);
            return false;
        }
    }

    async function loadUser(userId: number): Promise<boolean> {
        isLoading.value = true;

        const response = await usersService.call('get', userId);

        isLoading.value = false;

        if (response) {
            form.name = response.data.name || '';
            form.email = response.data.email || '';
            form.role = response.data.roles?.[0] || '';
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
        handleUpdate,
        loadUser,
    };
}
