import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useUsersService, useRolesService } from './index';

export interface UserFormI {
    id?: number;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
}

export interface UserValidationErrorsI {
    name?: string[];
    email?: string[];
    password?: string[];
    password_confirmation?: string[];
    role?: string[];
}

const initialFormState: UserFormI = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
};

const initialErrorsState: UserValidationErrorsI = {
    name: [],
    email: [],
    password: [],
    password_confirmation: [],
    role: [],
};

export function useUsers() {
    const router = useRouter();
    const usersService = useUsersService();
    const rolesService = useRolesService();

    const form = reactive<UserFormI>({ ...initialFormState });
    const errores = reactive<UserValidationErrorsI>({ ...initialErrorsState });
    const isLoading = ref(false);
    const successMessage = ref('');

    const localValidationErrors = reactive<UserValidationErrorsI>({ ...initialErrorsState });

    const passwordMatches = computed(() => form.password === form.password_confirmation);

    function resetForm(): void {
        form.name = '';
        form.email = '';
        form.password = '';
        form.password_confirmation = '';
        form.role = '';
        resetErrors();
        successMessage.value = '';
    }

    function resetErrors(): void {
        errores.name = [];
        errores.email = [];
        errores.password = [];
        errores.password_confirmation = [];
        errores.role = [];
        localValidationErrors.name = [];
        localValidationErrors.email = [];
        localValidationErrors.password = [];
        localValidationErrors.password_confirmation = [];
        localValidationErrors.role = [];
    }

    function validateForm(): boolean {
        localValidationErrors.name = [];
        localValidationErrors.email = [];
        localValidationErrors.password = [];
        localValidationErrors.password_confirmation = [];
        localValidationErrors.role = [];

        if (!form.password || form.password.length < 8) {
            localValidationErrors.password = ['El password debe tener al menos 8 caracteres.'];
        }

        if (!passwordMatches.value) {
            localValidationErrors.password_confirmation = ['La confirmacion de password no coincide.'];
        }

        const hasLocalErrors = Object.values(localValidationErrors).some(
            (errors) => Array.isArray(errors) && errors.length > 0,
        );

        return !hasLocalErrors;
    }

    function mapBackendErrors(): void {
        const backendErrors = usersService.validationErrors;
        errores.name = backendErrors.name || [];
        errores.email = backendErrors.email || [];
        errores.password = backendErrors.password || [];
        errores.password_confirmation = backendErrors.password_confirmation || [];
        errores.role = backendErrors.role || [];
    }

    async function handleCreate(): Promise<void> {
        successMessage.value = '';
        resetErrors();

        if (!validateForm()) {
            // Copy local errors to display
            Object.assign(errores, localValidationErrors);
            return;
        }

        isLoading.value = true;

        const response = await usersService.call('create', {
            name: form.name.trim(),
            email: form.email.trim(),
            password: form.password,
            password_confirmation: form.password_confirmation,
            role: form.role,
        });

        if (response) {
            successMessage.value = `Usuario creado con ID ${response.data.id}.`;
            resetForm();
        } else {
            mapBackendErrors();
        }

        isLoading.value = false;
    }

    async function handleUpdate(userId: number): Promise<void> {
        successMessage.value = '';
        resetErrors();

        // For update, only validate password if provided
        if (form.password) {
            if (form.password.length < 8) {
                localValidationErrors.password = ['El password debe tener al menos 8 caracteres.'];
            }
            if (form.password_confirmation && !passwordMatches.value) {
                localValidationErrors.password_confirmation = ['La confirmacion de password no coincide.'];
            }

            const hasLocalErrors = Object.values(localValidationErrors).some(
                (errors) => Array.isArray(errors) && errors.length > 0,
            );

            if (hasLocalErrors) {
                Object.assign(errores, localValidationErrors);
                return;
            }
        }

        isLoading.value = true;

        const response = await usersService.call('update', userId, {
            name: form.name.trim(),
            email: form.email.trim(),
            role: form.role,
            password: form.password || undefined,
        });

        if (response) {
            successMessage.value = `Usuario actualizado correctamente.`;
            form.password = '';
            form.password_confirmation = '';
        } else {
            mapBackendErrors();
        }

        isLoading.value = false;
    }

    async function handleDelete(userId: number): Promise<boolean> {
        isLoading.value = true;

        const response = await usersService.call('remove', userId);

        isLoading.value = false;

        if (response) {
            return true;
        }

        return false;
    }

    async function loadUser(userId: number): Promise<void> {
        isLoading.value = true;

        const response = await usersService.call('get', userId);

        if (response) {
            form.id = response.data.id;
            form.name = response.data.name || '';
            form.email = response.data.email || '';
            form.role = response.data.roles?.[0] || '';
            form.password = '';
            form.password_confirmation = '';
        }

        isLoading.value = false;
    }

    async function loadRoles(): Promise<void> {
        await rolesService.call('list');
    }

    return {
        form,
        errores,
        isLoading,
        successMessage,
        usersService,
        rolesService,
        passwordMatches,
        localValidationErrors,
        resetForm,
        resetErrors,
        validateForm,
        handleCreate,
        handleUpdate,
        handleDelete,
        loadUser,
        loadRoles,
        mapBackendErrors,
    };
}
