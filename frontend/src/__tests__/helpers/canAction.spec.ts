import { describe, it, expect, vi, beforeEach } from 'vitest';
import { canAction } from '@/helpers/canAction';
import * as http from '@/services/http';

describe('canAction', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });

    it('devuelve false cuando no hay token', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue(null);
        expect(canAction('Proyecto.Store')).toBe(false);
    });

    it('devuelve false con token vacío', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('');
        expect(canAction('Proyecto.Store')).toBe(false);
    });

    it('devuelve true cuando hay token activo', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        expect(canAction('Proyecto.Store')).toBe(true);
    });

    it('devuelve true para cualquier acción mientras haya token', () => {
        vi.spyOn(http, 'getAuthToken').mockReturnValue('token-valido-123');
        expect(canAction('Proyecto.Destroy')).toBe(true);
        expect(canAction('Ticket.Update')).toBe(true);
        expect(canAction('Tarea.Index')).toBe(true);
    });
});
