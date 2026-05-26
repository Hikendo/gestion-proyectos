import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTasks } from '@/composables/useTasks';
import { useAppStore } from '@/store/useAppStore';
import * as tasksService from '@/services/project-tasks.service';

const mockPush = vi.fn();
const mockParams = { projectId: '20' };

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: mockPush }),
    useRoute: () => ({ params: mockParams }),
}));

describe('useTasks', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('form tiene valores iniciales correctos', () => {
        const { form } = useTasks();
        expect(form.value.title).toBe('');
        expect(form.value.status).toBe('pending');
        expect(form.value.project_id).toBe(0);
    });

    it('handleStore en éxito redirige a tasks', async () => {
        vi.spyOn(tasksService, 'store').mockResolvedValueOnce({
            status: true, message: 'Creada', items: { id: 1 } as any,
        });

        const { handleStore } = useTasks();
        await handleStore();

        expect(mockPush).toHaveBeenCalledWith({ name: 'tasks', params: { projectId: 20 } });
    });

    it('handleStore en 422 popula errores', async () => {
        vi.spyOn(tasksService, 'store').mockResolvedValueOnce({
            status: false, message: 'Error',
            errors: { title: ['El título es obligatorio'], project_id: [], status: [] },
        });

        const { errores, handleStore } = useTasks();
        await handleStore();

        expect(errores.value.title).toContain('El título es obligatorio');
    });

    it('handleUpdate en éxito muestra snackbar success', async () => {
        vi.spyOn(tasksService, 'update').mockResolvedValueOnce({
            status: true, message: 'Actualizada', items: {} as any,
        });

        const { handleUpdate } = useTasks();
        const appStore = useAppStore();
        await handleUpdate();

        expect(appStore.snackbar.color).toBe('success');
        expect(appStore.snackbar.text).toBe('Tarea actualizada');
    });
});
