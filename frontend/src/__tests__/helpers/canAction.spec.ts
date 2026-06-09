import { describe, it, expect, vi, beforeEach } from 'vitest';
import { canAction } from '@/helpers/canAction';
import * as http from '@/services/http';
import { setActivePinia, createPinia } from 'pinia';
import { usePermissionStore } from '@/store/usePermissionStore';
import { useAuthStore } from '@/store/useAuthStore';

describe('canAction', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
        // Create a fresh Pinia instance for each test
        setActivePinia(createPinia());
    });

    it('devuelve false cuando no hay token', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue(null);
        expect(canAction('Proyecto.Store')).toBe(false);
    });

    it('devuelve false con token vacío', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('');
        expect(canAction('Proyecto.Store')).toBe(false);
    });

    it('devuelve true cuando hay token pero los permisos aún no están cargados (permissivo temporal)', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        // PermissionStore not loaded yet
        expect(canAction('Proyecto.Store')).toBe(true);
    });

    it('devuelve true cuando la acción está en los permisos cargados', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        const permStore = usePermissionStore();
        permStore.setPermissions(['Proyecto.Store', 'Proyecto.Update']);
        expect(canAction('Proyecto.Store')).toBe(true);
    });

    it('devuelve false cuando la acción no está en los permisos cargados', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        const permStore = usePermissionStore();
        permStore.setPermissions(['Proyecto.View']);
        expect(canAction('Proyecto.Destroy')).toBe(false);
    });

    it('devuelve true para acción -own cuando el usuario es dueño del recurso', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        const permStore = usePermissionStore();
        permStore.setPermissions(['Tarea.Edit-own']);

        const authStore = useAuthStore();
        authStore.authUser = { id: 42, name: 'Dev', email: 'dev@test.com' };

        expect(canAction('Tarea.Edit-own', 42)).toBe(true);
    });

    it('devuelve false para acción -own cuando el usuario NO es dueño del recurso', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        const permStore = usePermissionStore();
        permStore.setPermissions(['Tarea.Edit-own']);

        const authStore = useAuthStore();
        authStore.authUser = { id: 42, name: 'Dev', email: 'dev@test.com' };

        expect(canAction('Tarea.Edit-own', 99)).toBe(false);
    });
});