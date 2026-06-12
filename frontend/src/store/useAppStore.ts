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

    // Variables para guardar las referencias de los temporizadores
    let snackbarTimeout: ReturnType<typeof setTimeout> | null = null;
    let loaderTimeout: ReturnType<typeof setTimeout> | null = null;

    // --- Lógica del Snackbar ---

    function resetSnackbarTimeout() {
        // Limpiamos el temporizador anterior si existe
        if (snackbarTimeout) {
            clearTimeout(snackbarTimeout);
        }
        // Creamos un nuevo temporizador por 1.5 segundos (1500ms)
        snackbarTimeout = setTimeout(() => {
            snackbar.value.show = false;
        }, 1000);
    }

    function showSuccess(text: string) {
        snackbar.value = { show: true, text, color: 'success' };
        resetSnackbarTimeout();
    }

    function showError(text: string) {
        snackbar.value = { show: true, text, color: 'error' };
        resetSnackbarTimeout();
    }

    // --- Lógica del Loader ---

    function showLoader() {
        loader.value = true;
        
        // Limpiamos el temporizador anterior si existe
        if (loaderTimeout) {
            clearTimeout(loaderTimeout);
        }
        // Ocultamos el loader después de 1.5 segundos
        loaderTimeout = setTimeout(() => {
            loader.value = false;
        }, 1000);
    }

    // Recuerda exportar la nueva función showLoader
    return { 
        loader, 
        snackbar, 
        showSuccess, 
        showError, 
        showLoader 
    };
});