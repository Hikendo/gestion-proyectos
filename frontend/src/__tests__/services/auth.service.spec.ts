import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { apiWithToken } from '@/services/http';
import * as authService from '@/services/auth.service';
import * as http from '@/services/http';

vi.mock('@/services/http', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@/services/http')>();
    return {
        ...actual,
        setAuthToken: vi.fn(),
        clearAuthToken: vi.fn(),
        getAuthToken: vi.fn(),
    };
});

describe('auth.service', () => {
    beforeEach(() => vi.clearAllMocks());

    describe('login', () => {
        it('devuelve status true y llama setAuthToken en éxito', async () => {
            const mockData = {
                status: true,
                message: 'Login exitoso',
                items: { token: 'tok-123', user: { id: 1, name: 'Ana', email: 'ana@test.com' } },
            };
            vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: mockData });

            const result = await authService.login({ email: 'ana@test.com', password: 'secret' });

            expect(result.status).toBe(true);
            expect(result.message).toBe('Login exitoso');
            expect(http.setAuthToken).toHaveBeenCalledWith('tok-123');
        });

        it('devuelve status false y errors en error 422', async () => {
            const axiosError = {
                response: { status: 422, data: { errors: { email: ['Email incorrecto'] } } },
            };
            vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(axiosError);

            const result = await authService.login({ email: 'x@x.com', password: 'wrong' });

            expect(result.status).toBe(false);
            expect(result.message).toBe('Credenciales incorrectas');
        });

        it('devuelve status false en error de servidor', async () => {
            vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('Network Error'));

            const result = await authService.login({ email: 'x@x.com', password: 'pass' });

            expect(result.status).toBe(false);
            expect(result.message).toBe('Error en el servidor');
        });
    });

    describe('me', () => {
        it('devuelve el usuario autenticado', async () => {
            const mockData = {
                status: true,
                message: 'OK',
                items: { id: 1, name: 'Ana', email: 'ana@test.com' },
            };
            vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockData });

            const result = await authService.me();

            expect(result.status).toBe(true);
            expect((result as any).items.id).toBe(1);
        });

        it('devuelve status false en error de red', async () => {
            vi.spyOn(apiWithToken, 'get').mockRejectedValueOnce(new Error('fail'));

            const result = await authService.me();

            expect(result.status).toBe(false);
        });
    });

    describe('logout', () => {
        it('llama clearAuthToken en éxito', async () => {
            vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { status: true, message: 'OK' } });

            const result = await authService.logout();

            expect(result.status).toBe(true);
            expect(http.clearAuthToken).toHaveBeenCalled();
        });

        it('llama clearAuthToken aunque falle el request', async () => {
            vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('fail'));

            const result = await authService.logout();

            expect(result.status).toBe(false);
            expect(http.clearAuthToken).toHaveBeenCalled();
        });
    });
});
