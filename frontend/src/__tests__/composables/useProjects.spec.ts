import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { ref } from 'vue';
import { useProjects } from '@/composables/useProjects';
import { useAppStore } from '@/store/useAppStore';
import * as projectsService from '@/services/projects.service';

const mockPush = vi.fn();

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: mockPush }),
    useRoute: () => ({ params: {} }),
}));

describe('useProjects', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        mockPush.mockClear();
    });

    describe('estado inicial', () => {
        it('form tiene valores por defecto', () => {
            const { form } = useProjects();
            expect(form.value.id).toBe(0);
            expect(form.value.name).toBe('');
            expect(form.value.status).toBe('planning');
            expect(form.value.owner_id).toBe(0);
        });

        it('errores están vacíos al inicio', () => {
            const { errores } = useProjects();
            expect(errores.value.name).toEqual([]);
            expect(errores.value.status).toEqual([]);
            expect(errores.value.owner_id).toEqual([]);
        });
    });

    describe('handleStore', () => {
        it('en éxito: muestra snackbar success y redirige a projects', async () => {
            vi.spyOn(projectsService, 'store').mockResolvedValueOnce({
                status: true,
                message: 'Creado',
                items: { id: 5, name: 'Nuevo', status: 'planning', owner_id: 1 } as any,
            });

            const { form, handleStore } = useProjects();
            const appStore = useAppStore();
            form.value.name = 'Nuevo';
            form.value.owner_id = 1;

            await handleStore();

            expect(appStore.snackbar.show).toBe(true);
            expect(appStore.snackbar.color).toBe('success');
            expect(mockPush).toHaveBeenCalledWith({ name: 'projects' });
        });

        it('en error 422: popula errores y muestra snackbar error', async () => {
            vi.spyOn(projectsService, 'store').mockResolvedValueOnce({
                status: false,
                message: 'Llena correctamente el formulario',
                errors: { name: ['El nombre es obligatorio'], status: [], owner_id: [] },
            });

            const { errores, handleStore } = useProjects();
            const appStore = useAppStore();

            await handleStore();

            expect(errores.value.name).toContain('El nombre es obligatorio');
            expect(appStore.snackbar.color).toBe('error');
            expect(mockPush).not.toHaveBeenCalled();
        });

        it('en error de servidor: muestra snackbar error sin errores de campo', async () => {
            vi.spyOn(projectsService, 'store').mockResolvedValueOnce({
                status: false,
                message: 'Error en el servidor',
            });

            const { errores, handleStore } = useProjects();
            const appStore = useAppStore();

            await handleStore();

            expect(appStore.snackbar.color).toBe('error');
            expect(appStore.snackbar.text).toBe('Error en el servidor');
            expect(errores.value.name).toEqual([]);
        });
    });

    describe('handleUpdate', () => {
        it('en éxito: muestra snackbar success sin redirigir', async () => {
            vi.spyOn(projectsService, 'update').mockResolvedValueOnce({
                status: true,
                message: 'Actualizado',
                items: { id: 1, name: 'Actualizado', status: 'active', owner_id: 1 } as any,
            });

            const { form, handleUpdate } = useProjects();
            const appStore = useAppStore();
            form.value.id = 1;
            form.value.name = 'Actualizado';

            await handleUpdate();

            expect(appStore.snackbar.show).toBe(true);
            expect(appStore.snackbar.color).toBe('success');
            expect(mockPush).not.toHaveBeenCalled();
        });

        it('en error 422: popula errores', async () => {
            vi.spyOn(projectsService, 'update').mockResolvedValueOnce({
                status: false,
                message: 'Error validación',
                errors: { name: ['Nombre inválido'], status: [], owner_id: [] },
            });

            const { errores, handleUpdate } = useProjects();
            const appStore = useAppStore();

            await handleUpdate();

            expect(errores.value.name).toContain('Nombre inválido');
            expect(appStore.snackbar.color).toBe('error');
        });

        it('llama a projectsService.update con el id y payload del form', async () => {
            const updateSpy = vi.spyOn(projectsService, 'update').mockResolvedValueOnce({
                status: true,
                message: 'OK',
                items: {} as any,
            });

            const { form, handleUpdate } = useProjects();
            form.value = { id: 7, name: 'Test', status: 'planning', owner_id: 3, code: null, description: null, start_date: null, end_date: null, budget: null, progress: null };

            await handleUpdate();

            expect(updateSpy).toHaveBeenCalledWith(7, expect.objectContaining({ name: 'Test', owner_id: 3 }));
        });
    });
});
