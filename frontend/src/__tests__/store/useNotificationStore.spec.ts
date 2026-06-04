import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useNotificationStore } from '@/store/useNotificationStore';
import * as notificationsService from '@/services/notifications.service';
import type { NotificationI, NotificationsPaginatedResponse } from '@/interfaces/NotificationI';

function makeNotification(overrides: Partial<NotificationI> = {}): NotificationI {
  return {
    id: 1,
    user_id: 1,
    title: 'Test Notification',
    body: 'This is a test',
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

function makePaginatedResponse(
  notifications: NotificationI[],
  overrides: Partial<NotificationsPaginatedResponse> = {}
): NotificationsPaginatedResponse {
  return {
    data: notifications,
    links: { first: null, last: null, prev: null, next: null },
    meta: {
      current_page: 1,
      from: notifications.length > 0 ? 1 : null,
      last_page: 1,
      links: [],
      path: '/notifications',
      per_page: 10,
      to: notifications.length > 0 ? notifications.length : null,
      total: notifications.length,
    },
    ...overrides,
  };
}

describe('useNotificationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  describe('estado inicial', () => {
    it('notifications empieza vacío', () => {
      const store = useNotificationStore();
      expect(store.notifications).toEqual([]);
    });

    it('unreadCount empieza en 0', () => {
      const store = useNotificationStore();
      expect(store.unreadCount).toBe(0);
    });

    it('trayOpen empieza cerrado', () => {
      const store = useNotificationStore();
      expect(store.trayOpen).toBe(false);
    });

    it('hasUnread es false al inicio', () => {
      const store = useNotificationStore();
      expect(store.hasUnread).toBe(false);
    });
  });

  describe('fetchNotifications', () => {
    it('carga notificaciones paginadas y actualiza meta', async () => {
      const items = [makeNotification({ id: 1 }), makeNotification({ id: 2, read_at: '2026-06-05T01:00:00.000Z' })];
      const response = makePaginatedResponse(items, {
        meta: { current_page: 2, from: 11, last_page: 5, links: [], path: '/notifications', per_page: 10, to: 12, total: 42 },
      });
      vi.spyOn(notificationsService, 'fetchNotifications').mockResolvedValueOnce(response);

      const store = useNotificationStore();
      await store.fetchNotifications(2);

      expect(store.notifications).toHaveLength(2);
      expect(store.currentPage).toBe(2);
      expect(store.lastPage).toBe(5);
      expect(store.total).toBe(42);
      expect(store.loading).toBe(false);
      // unreadCount: solo la primera no está leída
      expect(store.unreadCount).toBe(1);
      expect(store.hasUnread).toBe(true);
    });

    it('maneja respuesta que es array directo (fallback)', async () => {
      const items = [makeNotification({ id: 1 }), makeNotification({ id: 2 })];
      // Simulamos que el backend devuelve array directo
      vi.spyOn(notificationsService, 'fetchNotifications').mockResolvedValueOnce(items as unknown as NotificationsPaginatedResponse);

      const store = useNotificationStore();
      await store.fetchNotifications();

      expect(store.notifications).toHaveLength(2);
      expect(store.unreadCount).toBe(2);
    });

    it('maneja respuesta sin data (vacío)', async () => {
      vi.spyOn(notificationsService, 'fetchNotifications').mockResolvedValueOnce({} as NotificationsPaginatedResponse);

      const store = useNotificationStore();
      await store.fetchNotifications();

      expect(store.notifications).toEqual([]);
    });

    it('maneja error sin romper', async () => {
      vi.spyOn(notificationsService, 'fetchNotifications').mockRejectedValueOnce(new Error('Network Error'));

      const store = useNotificationStore();
      await store.fetchNotifications();

      expect(store.loading).toBe(false);
      expect(store.notifications).toEqual([]);
    });

    it('actualiza unreadCount según notificaciones con read_at null', async () => {
      const items = [
        makeNotification({ id: 1, read_at: null }),
        makeNotification({ id: 2, read_at: '2026-06-05T00:00:00.000Z' }),
        makeNotification({ id: 3, read_at: null }),
      ];
      vi.spyOn(notificationsService, 'fetchNotifications').mockResolvedValueOnce(makePaginatedResponse(items));

      const store = useNotificationStore();
      await store.fetchNotifications();

      expect(store.unreadCount).toBe(2);
      expect(store.hasUnread).toBe(true);
    });
  });

  describe('refreshUnreadCount', () => {
    it('actualiza unreadCount desde el servicio', async () => {
      vi.spyOn(notificationsService, 'fetchUnreadCount').mockResolvedValueOnce(7);

      const store = useNotificationStore();
      await store.refreshUnreadCount();

      expect(store.unreadCount).toBe(7);
      expect(store.hasUnread).toBe(true);
    });

    it('maneja error sin romper', async () => {
      vi.spyOn(notificationsService, 'fetchUnreadCount').mockRejectedValueOnce(new Error('fail'));

      const store = useNotificationStore();
      await store.refreshUnreadCount();

      expect(store.unreadCount).toBe(0);
    });
  });

  describe('markAsRead', () => {
    it('marca una notificación como leída y actualiza unreadCount', async () => {
      vi.spyOn(notificationsService, 'markNotificationRead').mockResolvedValueOnce();
      const store = useNotificationStore();
      store.notifications = [
        makeNotification({ id: 1, read_at: null }),
        makeNotification({ id: 2, read_at: null }),
      ];
      store.unreadCount = 2;

      await store.markAsRead(1);

      expect(notificationsService.markNotificationRead).toHaveBeenCalledWith(1);
      expect(store.notifications[0].read_at).not.toBeNull();
      expect(store.notifications[1].read_at).toBeNull();
      expect(store.unreadCount).toBe(1);
    });

    it('no falla si la notificación no existe', async () => {
      vi.spyOn(notificationsService, 'markNotificationRead').mockResolvedValueOnce();
      const store = useNotificationStore();
      store.notifications = [makeNotification({ id: 1 })];
      store.unreadCount = 1;

      await store.markAsRead(999);

      expect(store.unreadCount).toBe(1);
    });

    it('maneja error sin romper', async () => {
      vi.spyOn(notificationsService, 'markNotificationRead').mockRejectedValueOnce(new Error('fail'));
      const store = useNotificationStore();
      store.notifications = [makeNotification({ id: 1, read_at: null })];
      store.unreadCount = 1;

      await store.markAsRead(1);

      // No debe cambiar porque falló
      expect(store.notifications[0].read_at).toBeNull();
      expect(store.unreadCount).toBe(1);
    });
  });

  describe('markAllAsRead', () => {
    it('marca todas como leídas y pone unreadCount en 0', async () => {
      vi.spyOn(notificationsService, 'markAllNotificationsRead').mockResolvedValueOnce();
      const store = useNotificationStore();
      store.notifications = [
        makeNotification({ id: 1, read_at: null }),
        makeNotification({ id: 2, read_at: '2026-06-05T00:00:00.000Z' }),
        makeNotification({ id: 3, read_at: null }),
      ];
      store.unreadCount = 2;

      await store.markAllAsRead();

      expect(notificationsService.markAllNotificationsRead).toHaveBeenCalled();
      expect(store.notifications.every((n) => n.read_at !== null)).toBe(true);
      expect(store.unreadCount).toBe(0);
      expect(store.hasUnread).toBe(false);
    });

    it('maneja error sin romper', async () => {
      vi.spyOn(notificationsService, 'markAllNotificationsRead').mockRejectedValueOnce(new Error('fail'));
      const store = useNotificationStore();
      store.notifications = [makeNotification({ id: 1, read_at: null })];
      store.unreadCount = 1;

      await store.markAllAsRead();

      // No debe cambiar porque falló
      expect(store.notifications[0].read_at).toBeNull();
      expect(store.unreadCount).toBe(1);
    });
  });

  describe('addNotificationFromFcm', () => {
    it('inserta al inicio del array y actualiza unreadCount', () => {
      const store = useNotificationStore();
      // Notificación existente YA LEÍDA — no debe sumar al unreadCount
      store.notifications = [makeNotification({ id: 1, read_at: '2026-06-05T00:00:00.000Z' })];
      store.unreadCount = 0;

      const fcmNotification = makeNotification({ id: 99, title: 'Nueva desde FCM', read_at: null });
      store.addNotificationFromFcm(fcmNotification);

      expect(store.notifications).toHaveLength(2);
      expect(store.notifications[0].id).toBe(99);
      // Solo la nueva (FCM) cuenta como no leída
      expect(store.unreadCount).toBe(1);
    });

    it('no incrementa unreadCount si ya estaba leída', () => {
      const store = useNotificationStore();
      store.notifications = [];
      store.unreadCount = 0;

      const fcmNotification = makeNotification({ id: 99, read_at: '2026-06-05T00:00:00.000Z' });
      store.addNotificationFromFcm(fcmNotification);

      expect(store.unreadCount).toBe(0);
    });
  });

  describe('toggleTray / closeTray / openTray', () => {
    it('toggleTray alterna trayOpen', () => {
      const store = useNotificationStore();
      expect(store.trayOpen).toBe(false);

      store.toggleTray();
      expect(store.trayOpen).toBe(true);

      store.toggleTray();
      expect(store.trayOpen).toBe(false);
    });

    it('closeTray cierra la bandeja', () => {
      const store = useNotificationStore();
      store.trayOpen = true;
      store.closeTray();
      expect(store.trayOpen).toBe(false);
    });

    it('openTray abre la bandeja', () => {
      const store = useNotificationStore();
      store.openTray();
      expect(store.trayOpen).toBe(true);
    });
  });

  describe('unreadNotifications getter', () => {
    it('devuelve solo las no leídas', () => {
      const store = useNotificationStore();
      store.notifications = [
        makeNotification({ id: 1, read_at: null }),
        makeNotification({ id: 2, read_at: '2026-06-05T00:00:00.000Z' }),
        makeNotification({ id: 3, read_at: null }),
      ];

      const unread = store.unreadNotifications;
      expect(unread).toHaveLength(2);
      expect(unread.map((n) => n.id)).toEqual([1, 3]);
    });
  });
});