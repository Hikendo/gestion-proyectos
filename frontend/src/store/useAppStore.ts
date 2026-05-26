import { defineStore } from 'pinia';
import { ref } from 'vue';

export interface SnackbarState {
    show: boolean;
    text: string;
    color: 'success' | 'error' | 'warning' | 'info';
}

export const useAppStore = defineStore('app', () => {
    const loader = ref<boolean>(false);

    const snackbar = ref<SnackbarState>({
        show: false,
        text: '',
        color: 'success',
    });

    function showSuccess(text: string) {
        snackbar.value = { show: true, text, color: 'success' };
    }

    function showError(text: string) {
        snackbar.value = { show: true, text, color: 'error' };
    }

    return { loader, snackbar, showSuccess, showError };
});
