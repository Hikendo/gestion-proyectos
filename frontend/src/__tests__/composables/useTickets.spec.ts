import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTickets } from '@/composables/useTickets';
import { useAppStore } from '@/store/useAppStore';
import * as ticketsService from '@/services/tickets.service';

const mockPush = vi.fn();
const mockParams = { projectId: '10' };

vi.mock('vue-router', () => ({
    useRouter: () => ({ push: mockPush }),
    useRoute: () => ({ params: mockParams }),
}));

describe('useTickets', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    describe('estado inicial', () => {
        it('form tiene valores por defecto', () => {
            const { form } = useTickets();
            expect(form.value.id).toBe(0);
            expect(form.value.subject).toBe('');
            expect(form.value.status).toBe('open');
            expect(form.value.priority).toBe('medium');
        });
    });

    describe('handleStore', () => {
        it('en éxito: redirige a tickets con projectId', async () => {
            vi.spyOn(ticketsService, 'store').mockResolvedValueOnce({
                status: true,
                message: 'Creado',
                items: { id: 1 } as any,
            });

            const { handleStore } = useTickets();
            await handleStore();

            expect(mockPush).toHaveBeenCalledWith({ name: 'tickets', params: { projectId: 10 } });
        });

        it('llama a ticketsService.store con el projectId de la ruta', async () => {
            const storeSpy = vi.spyOn(ticketsService, 'store').mockResolvedValueOnce({
                status: true,
                message: 'OK',
                items: {} as any,
            });

            const { handleStore } = useTickets();
            await handleStore();

            expect(storeSpy).toHaveBeenCalledWith(10, expect.any(Object));
        });

        it('en error 422: popula errores', async () => {
            vi.spyOn(ticketsService, 'store').mockResolvedValueOnce({
                status: false,
                message: 'Error',
                errors: { subject: ['Asunto requerido'], project_id: [], status: [], priority: [] },
            });

            const { errores, handleStore } = useTickets();
            await handleStore();

            expect(errores.value.subject).toContain('Asunto requerido');
        });
    });

    describe('handleUpdate', () => {
        it('en éxito: muestra snackbar success', async () => {
            vi.spyOn(ticketsService, 'update').mockResolvedValueOnce({
                status: true,
                message: 'Actualizado',
                items: {} as any,
            });

            const { handleUpdate } = useTickets();
            const appStore = useAppStore();
            await handleUpdate();

            expect(appStore.snackbar.color).toBe('success');
            expect(appStore.snackbar.text).toBe('Ticket actualizado');
        });

        it('llama a ticketsService.update con projectId correcto', async () => {
            const updateSpy = vi.spyOn(ticketsService, 'update').mockResolvedValueOnce({
                status: true,
                message: 'OK',
                items: {} as any,
            });

            const { form, handleUpdate } = useTickets();
            form.value.id = 5;
            await handleUpdate();

            expect(updateSpy).toHaveBeenCalledWith(10, 5, expect.any(Object));
        });
    });
});
