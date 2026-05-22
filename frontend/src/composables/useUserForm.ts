import { reactive, ref } from 'vue';
import type { User, UserValidationErrors } from '../interfaces/UserI';

export interface UserFormState {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role: string;
}

const initialFormState: UserFormState = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
};

const initialErrorsState: UserValidationErrors = {
    name: [],
    email: [],
    password: [],
    password_confirmation: [],
    role: [],
};

export function useUserForm() {
    const form = reactive<UserFormState>({ ...initialFormState });
    const errors = reactive<UserValidationErrors>({ ...initialErrorsState });
    const localErrors = reactive<UserValidationErrors>({ ...initialErrorsState });

    function resetForm(): void {
        form.name = '';
        form.email = '';
        form.password = '';
        form.password_confirmation = '';
        form.role = '';
        resetErrors();
    }

    function resetErrors(): void {
        errors.name = [];
        errors.email = [];
        errors.password = [];
        errors.password_confirmation = [];
        errors.role = [];
        localErrors.name = [];
        localErrors.email = [];
        localErrors.password = [];
        localErrors.password_confirmation = [];
        localErrors.role = [];
    }

    function setBackendErrors(backendErrors: UserValidationErrors): void {
        errors.name = backendErrors.name || [];
        errors.email = backendErrors.email || [];
        errors.password = backendErrors.password || [];
        errors.password_confirmation = backendErrors.password_confirmation || [];
        errors.role = backendErrors.role || [];
    }

    function setLocalError(field: keyof UserValidationErrors, message: string): void {
        if (!localErrors[field]) {
            localErrors[field] = [];
        }
        (localErrors[field] as string[]).push(message);
    }

    function clearLocalErrors(): void {
        localErrors.name = [];
        localErrors.email = [];
        localErrors.password = [];
        localErrors.password_confirmation = [];
        localErrors.role = [];
    }

    function getFieldErrors(field: keyof UserValidationErrors): string[] {
        const backendErrs = errors[field] || [];
        const localErrs = localErrors[field] || [];
        return [...localErrs, ...backendErrs];
    }

    return {
        form,
        errors,
        localErrors,
        resetForm,
        resetErrors,
        setBackendErrors,
        setLocalError,
        clearLocalErrors,
        getFieldErrors,
    };
}
