import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAppStore } from '@/store/useAppStore';

describe('useAppStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('tiene el estado inicial correcto', () => {
        const store = useAppStore();
        expect(store.loader).toBe(false);
        expect(store.snackbar.show).toBe(false);
        expect(store.snackbar.text).toBe('');
        expect(store.snackbar.color).toBe('success');
    });

    it('showSuccess actualiza el snackbar con color success', () => {
        const store = useAppStore();
        store.showSuccess('Operación exitosa');
        expect(store.snackbar.show).toBe(true);
        expect(store.snackbar.text).toBe('Operación exitosa');
        expect(store.snackbar.color).toBe('success');
    });

    it('showError actualiza el snackbar con color error', () => {
        const store = useAppStore();
        store.showError('Algo salió mal');
        expect(store.snackbar.show).toBe(true);
        expect(store.snackbar.text).toBe('Algo salió mal');
        expect(store.snackbar.color).toBe('error');
    });

    it('loader puede activarse y desactivarse', () => {
        const store = useAppStore();
        store.loader = true;
        expect(store.loader).toBe(true);
        store.loader = false;
        expect(store.loader).toBe(false);
    });

    it('el snackbar puede ocultarse manualmente', () => {
        const store = useAppStore();
        store.showSuccess('Listo');
        store.snackbar.show = false;
        expect(store.snackbar.show).toBe(false);
    });
});
