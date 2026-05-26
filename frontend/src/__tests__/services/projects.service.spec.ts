import { describe, it, expect, vi, beforeEach } from 'vitest';
import { apiWithToken } from '@/services/http';
import * as projectsService from '@/services/projects.service';

describe('projects.service', () => {
    beforeEach(() => vi.clearAllMocks());

    const mockProject = {
        id: 1, name: 'Proyecto Alpha', code: 'PA', status: 'planning',
        owner_id: 1, description: null, start_date: null, end_date: null,
        budget: null, progress: null,
    };

    describe('index', () => {
        it('devuelve items paginados en éxito', async () => {
            const mockData = { status: true, message: 'OK', items: { data: [mockProject], last_page: 1 } };
            vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockData });

            const result = await projectsService.index();

            expect(result.status).toBe(true);
            expect((result.items as any).data).toHaveLength(1);
        });

        it('acepta parámetros de query', async () => {
            const getSpy = vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({
                data: { status: true, message: 'OK', items: { data: [], last_page: 1 } },
            });

            await projectsService.index({ query: 'alpha', page: 2 });

            expect(getSpy).toHaveBeenCalledWith('/projects', { params: { query: 'alpha', page: 2 } });
        });

        it('devuelve status false en error', async () => {
            vi.spyOn(apiWithToken, 'get').mockRejectedValueOnce(new Error('fail'));

            const result = await projectsService.index();

            expect(result.status).toBe(false);
        });
    });

    describe('show', () => {
        it('devuelve el proyecto por id', async () => {
            const mockData = { status: true, message: 'OK', items: mockProject };
            vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockData });

            const result = await projectsService.show(1);

            expect(result.status).toBe(true);
            expect((result as any).items.id).toBe(1);
        });

        it('devuelve status false en error', async () => {
            vi.spyOn(apiWithToken, 'get').mockRejectedValueOnce(new Error('fail'));

            const result = await projectsService.show(99);

            expect(result.status).toBe(false);
        });
    });

    describe('store', () => {
        it('crea el proyecto y devuelve status true', async () => {
            const mockData = { status: true, message: 'Creado', items: { ...mockProject, id: 5 } };
            vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: mockData });

            const result = await projectsService.store({ name: 'Proyecto Alpha', status: 'planning', owner_id: 1 });

            expect(result.status).toBe(true);
            expect((result as any).items.id).toBe(5);
        });

        it('devuelve errores de validación en error 422', async () => {
            const axiosError = {
                response: {
                    status: 422,
                    data: { errors: { name: ['El nombre es obligatorio'] } },
                },
            };
            vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(axiosError);

            const result = await projectsService.store({ name: '', status: 'planning', owner_id: 1 });

            expect(result.status).toBe(false);
            expect((result as any).errors.name).toContain('El nombre es obligatorio');
        });

        it('devuelve status false en error genérico', async () => {
            vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('fail'));

            const result = await projectsService.store({ name: 'X', status: 'planning', owner_id: 1 });

            expect(result.status).toBe(false);
            expect(result.message).toBe('Error en el servidor');
        });
    });

    describe('update', () => {
        it('actualiza el proyecto y devuelve status true', async () => {
            const mockData = { status: true, message: 'Actualizado', items: mockProject };
            vi.spyOn(apiWithToken, 'put').mockResolvedValueOnce({ data: mockData });

            const result = await projectsService.update(1, { name: 'Actualizado', status: 'active', owner_id: 1 });

            expect(result.status).toBe(true);
        });

        it('devuelve errores de validación en 422', async () => {
            const axiosError = {
                response: {
                    status: 422,
                    data: { errors: { status: ['Estado inválido'] } },
                },
            };
            vi.spyOn(apiWithToken, 'put').mockRejectedValueOnce(axiosError);

            const result = await projectsService.update(1, { name: 'X', status: 'invalid' as any, owner_id: 1 });

            expect(result.status).toBe(false);
            expect((result as any).errors.status).toBeDefined();
        });
    });

    describe('destroy', () => {
        it('elimina el proyecto y devuelve status true', async () => {
            vi.spyOn(apiWithToken, 'delete').mockResolvedValueOnce({ data: { status: true, message: 'Eliminado' } });

            const result = await projectsService.destroy(1);

            expect(result.status).toBe(true);
        });

        it('devuelve status false en error', async () => {
            vi.spyOn(apiWithToken, 'delete').mockRejectedValueOnce(new Error('fail'));

            const result = await projectsService.destroy(1);

            expect(result.status).toBe(false);
        });
    });
});
