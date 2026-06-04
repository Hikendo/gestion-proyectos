import { describe, it, expect, vi, beforeEach } from 'vitest';
import { apiWithToken } from '@/services/http';
import * as notificationsService from '@/services/notifications.service';
import type { NotificationI, NotificationsPaginatedResponse } from '@/interfaces/NotificationI';

function makeNotification(overrides: Partial<NotificationI> = {}): NotificationI {
  return {
    id: 1,
    user_id: 1,
    title: 'Test',
    body: 'Body',
    type: 'info',
    data: null,
    status: 'sent',
    sent_at: '2026-06-05T00:00:00.000Z',
    read_at: null,
    created_at: '2026-06-05T00:00:00.000Z',
    updated_at: '2026-06-05T00:00:00.000Z',
    ...overrides,
  };
}

describe('notifications.service', () => {
  beforeEach(() => vi.clearAllMocks());

  describe('fetchNotifications', () => {
    it('devuelve la respuesta paginada del backend', async () => {
      const mockResponse: NotificationsPaginatedResponse = {
        data: [makeNotification({ id: 1 }), makeNotification({ id: 2, read_at: '2026-06-05T01:00:00.000Z' })],
        links: { first: null, last: null, prev: null, next: null },
        meta: {
          current_page: 1,
          from: 1,
          last_page: 3,
          links: [],
          path: '/notifications',
          per_page: 10,
          to: 10,
          total: 25,
        },
      };
      const getSpy = vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockResponse });

      const result = await notificationsService.fetchNotifications(2);

      expect(getSpy).toHaveBeenCalledWith('/notifications', { params: { page: 2 } });
      expect(result).toEqual(mockResponse);
      expect(result.data).toHaveLength(2);
      expect(result.meta.current_page).toBe(1);
      expect(result.meta.total).toBe(25);
    });

    it('usa página 1 por defecto', async () => {
      const mockResponse: NotificationsPaginatedResponse = {
        data: [],
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, from: null, last_page: 1, links: [], path: '/notifications', per_page: 10, to: null, total: 0 },
      };
      vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockResponse });

      await notificationsService.fetchNotifications();

      expect(apiWithToken.get).toHaveBeenCalledWith('/notifications', { params: { page: 1 } });
    });

    it('propaga errores de red', async () => {
      vi.spyOn(apiWithToken, 'get').mockRejectedValueOnce(new Error('Network Error'));

      await expect(notificationsService.fetchNotifications()).rejects.toThrow('Network Error');
    });
  });

  describe('markNotificationRead', () => {
    it('envía notification_id al backend', async () => {
      const postSpy = vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { status: true } });

      await notificationsService.markNotificationRead(42);

      expect(postSpy).toHaveBeenCalledWith('/notifications/mark-read', {
        notification_id: 42,
      });
    });

    it('propaga errores', async () => {
      vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('fail'));

      await expect(notificationsService.markNotificationRead(1)).rejects.toThrow('fail');
    });
  });

  describe('markAllNotificationsRead', () => {
    it('llama al endpoint sin parámetros', async () => {
      const postSpy = vi.spyOn(apiWithToken, 'post').mockResolvedValueOnce({ data: { status: true } });

      await notificationsService.markAllNotificationsRead();

      expect(postSpy).toHaveBeenCalledWith('/notifications/mark-all-read');
    });

    it('propaga errores', async () => {
      vi.spyOn(apiWithToken, 'post').mockRejectedValueOnce(new Error('fail'));

      await expect(notificationsService.markAllNotificationsRead()).rejects.toThrow('fail');
    });
  });

  describe('fetchUnreadCount', () => {
    it('cuenta notificaciones con read_at null en la primera página', async () => {
      const mockResponse: NotificationsPaginatedResponse = {
        data: [
          makeNotification({ id: 1, read_at: null }),
          makeNotification({ id: 2, read_at: '2026-06-05T00:00:00.000Z' }),
          makeNotification({ id: 3, read_at: null }),
          makeNotification({ id: 4, read_at: null }),
        ],
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, from: 1, last_page: 1, links: [], path: '/notifications', per_page: 50, to: 4, total: 4 },
      };
      vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockResponse });

      const count = await notificationsService.fetchUnreadCount();

      expect(count).toBe(3);
      expect(apiWithToken.get).toHaveBeenCalledWith('/notifications', {
        params: { per_page: 50, page: 1 },
      });
    });

    it('devuelve 0 cuando todas están leídas', async () => {
      const mockResponse: NotificationsPaginatedResponse = {
        data: [
          makeNotification({ id: 1, read_at: '2026-06-05T00:00:00.000Z' }),
          makeNotification({ id: 2, read_at: '2026-06-05T01:00:00.000Z' }),
        ],
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, from: 1, last_page: 1, links: [], path: '/notifications', per_page: 50, to: 2, total: 2 },
      };
      vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockResponse });

      const count = await notificationsService.fetchUnreadCount();

      expect(count).toBe(0);
    });

    it('devuelve 0 cuando no hay notificaciones', async () => {
      const mockResponse: NotificationsPaginatedResponse = {
        data: [],
        links: { first: null, last: null, prev: null, next: null },
        meta: { current_page: 1, from: null, last_page: 1, links: [], path: '/notifications', per_page: 50, to: null, total: 0 },
      };
      vi.spyOn(apiWithToken, 'get').mockResolvedValueOnce({ data: mockResponse });

      const count = await notificationsService.fetchUnreadCount();

      expect(count).toBe(0);
    });

    it('propaga errores de red', async () => {
      vi.spyOn(apiWithToken, 'get').mockRejectedValueOnce(new Error('Network Error'));

      await expect(notificationsService.fetchUnreadCount()).rejects.toThrow('Network Error');
    });
  });
});