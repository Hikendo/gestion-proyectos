import { ref } from 'vue';
import { useUsersService } from './index';
import { useUserForm, type UserFormState } from './useUserForm';

export function useUserCreate() {
    const usersService = useUsersService();
    const { form, errors, clearLocalErrors, setBackendErrors, setLocalError } = useUserForm();
    const isLoading = ref(false);
    const successMessage = ref('');

    function validateForm(): boolean {
        clearLocalErrors();

        if (!form.password || form.password.length < 8) {
            setLocalError('password', 'El password debe tener al menos 8 caracteres.');
        }

        if (form.password !== form.password_confirmation) {
            setLocalError('password_confirmation', 'La confirmacion de password no coincide.');
        }

        // Return true if valid (no errors)
        return form.password && form.password.length >= 8 && form.password === form.password_confirmation;
    }

    async function handleCreate(): Promise<boolean> {
        successMessage.value = '';
        clearLocalErrors();

        if (!validateForm()) {
            return false;
        }

        isLoading.value = true;

        const response = await usersService.call('create', {
            name: form.name.trim(),
            email: form.email.trim(),
            password: form.password,
            password_confirmation: form.password_confirmation,
            role: form.role,
        });

        isLoading.value = false;

        if (response) {
            successMessage.value = `Usuario creado con ID ${response.data.id}.`;
            return true;
        } else {
            setBackendErrors(usersService.validationErrors);
            return false;
        }
    }

    return {
        form,
        errors,
        isLoading,
        successMessage,
        usersService,
        handleCreate,
    };
}
