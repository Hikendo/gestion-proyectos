import { computed, ref } from 'vue';
import {
    createFieldErrors,
    getApiErrorMessage,
    getApiValidationErrors,
    type FieldErrors,
} from '../services';

export function useServiceRequest<TField extends string>(fields: readonly TField[] = []) {
    const loading = ref(false);
    const errorMessage = ref('');
    const validationErrors = ref<FieldErrors<TField>>(createFieldErrors(fields));

    const hasValidationErrors = computed(() =>
        Object.values(validationErrors.value).some((messages) => Array.isArray(messages) && messages.length > 0),
    );

    const hasError = computed(() => Boolean(errorMessage.value) || hasValidationErrors.value);

    function resetErrors(): void {
        errorMessage.value = '';
        validationErrors.value = createFieldErrors(fields);
    }

    async function execute<TResult>(action: () => Promise<TResult>): Promise<TResult | null> {
        resetErrors();
        loading.value = true;

        try {
            return await action();
        } catch (error) {
            errorMessage.value = getApiErrorMessage(error);
            validationErrors.value = getApiValidationErrors(error, fields);

            return null;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        errorMessage,
        validationErrors,
        hasValidationErrors,
        hasError,
        resetErrors,
        execute,
    };
}
