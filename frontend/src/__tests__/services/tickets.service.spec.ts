import { describe, it, expect, vi, beforeEach } from 'vitest';
import { apiWithToken } from '@/services/http';
import * as ticketsService from '@/services/tickets.service';

describe('tickets.service', () => {
    beforeEach(() => vi.clearAllMocks());

    const mockTicket = {
        id: 1, project_id: 10, subject: 'Bug crítico', status: 'open',
        priority: 'high', description: null, created_by: 1, assigned_to: null,
    };

    describe('index', () => {
        it('devuelve lista de tickets del proyecto', async () => {
            const mockData = { status: true, message: 'OK', items: { data: [mockTicket], last_page: 1 } };
            vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockData });

            const result = await ticketsService.index(10);

            expect(result.status).toBe(true);
            expect((result.items as any).data[0].subject).toBe('Bug crítico');
        });

        it('llama al endpoint correcto con projectId', async () => {
            const getSpy = vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({
                data: { status: true, message: 'OK', items: { data: [], last_page: 1 } },
            });

            await ticketsService.index(42, { query: 'bug' });

            expect(getSpy).toHaveBeenCalledWith('/projects/42/tickets', expect.anything());
        });
    });

    describe('show', () => {
        it('devuelve el ticket por id', async () => {
            const mockData = { status: true, message: 'OK', items: mockTicket };
            vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockData });

            const result = await ticketsService.show(10, 1);

            expect(result.status).toBe(true);
            expect((result as any).items.id).toBe(1);
        });
    });

    describe('store', () => {
        it('crea ticket y devuelve status true', async () => {
            const mockData = { status: true, message: 'Creado', items: mockTicket };
            vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: mockData });

            const result = await ticketsService.store(10, {
                subject: 'Bug crítico', status: 'open', priority: 'high',
            });

            expect(result.status).toBe(true);
        });

        it('devuelve errores de validación en 422', async () => {
            const axiosError = {
                response: {
                    status: 422,
                    data: { errors: { subject: ['El asunto es obligatorio'] } },
                },
            };
            vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(axiosError);

            const result = await ticketsService.store(10, { subject: '', status: 'open', priority: 'medium' });

            expect(result.status).toBe(false);
            expect((result as any).errors.subject).toContain('El asunto es obligatorio');
        });
    });

    describe('update', () => {
        it('actualiza ticket y devuelve status true', async () => {
            vi.spyOn(apiWithToken, 'put').mockResolvedValueOnce({
                data: { status: true, message: 'OK', items: mockTicket },
            });

            const result = await ticketsService.update(10, 1, { subject: 'Actualizado', status: 'closed', priority: 'low' });

            expect(result.status).toBe(true);
        });
    });

    describe('destroy', () => {
        it('elimina el ticket y devuelve status true', async () => {
            vi.spyOn(apiWithToken, 'delete').mockResolvedValueOnce({ data: { status: true, message: 'Eliminado' } });

            const result = await ticketsService.destroy(10, 1);

            expect(result.status).toBe(true);
        });
    });
});
