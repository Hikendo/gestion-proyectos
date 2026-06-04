import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { NotificationI } from '@/interfaces/NotificationI';
import * as notificationsService from '@/services/notifications.service';

export const useNotificationStore = defineStore('notifications', () => {
  // ── State ────────────────────────────────────────────────────────
  const notifications = ref<NotificationI[]>([]);
  const unreadCount = ref(0);
  const loading = ref(false);
  const currentPage = ref(1);
  const lastPage = ref(1);
  const total = ref(0);
  const trayOpen = ref(false);

  // ── Getters ──────────────────────────────────────────────────────
  const unreadNotifications = computed(() =>
    notifications.value.filter((n: NotificationI) => n.read_at === null)
  );

  const hasUnread = computed(() => unreadCount.value > 0);

  // ── Actions ──────────────────────────────────────────────────────
  async function fetchNotifications(page = 1) {
    loading.value = true;
    try {
      const response = await notificationsService.fetchNotifications(page);
      // El backend devuelve { data: NotificationI[], links: {...}, meta: {...} }
      // Si la estructura no coincide, asumimos array vacío
      if (response && Array.isArray(response.data)) {
        notifications.value = response.data;
      } else if (Array.isArray(response)) {
        // Por si el backend devuelve el array directamente
        notifications.value = response as unknown as NotificationI[];
      } else {
        notifications.value = [];
      }
      if (response?.meta) {
        currentPage.value = response.meta.current_page ?? 1;
        lastPage.value = response.meta.last_page ?? 1;
        total.value = response.meta.total ?? 0;
      }
      updateUnreadCount();
    } catch (error) {
      console.error('Error fetching notifications:', error);
    } finally {
      loading.value = false;
    }
  }

  async function refreshUnreadCount() {
    try {
      unreadCount.value = await notificationsService.fetchUnreadCount();
    } catch (error) {
      console.error('Error fetching unread count:', error);
    }
  }

  function updateUnreadCount() {
    unreadCount.value = notifications.value.filter((n: NotificationI) => n.read_at === null).length;
  }

  async function markAsRead(notificationId: number) {
    try {
      await notificationsService.markNotificationRead(notificationId);
      // Actualizar localmente
      const notification = notifications.value.find((n: NotificationI) => n.id === notificationId);
      if (notification) {
        notification.read_at = new Date().toISOString();
      }
      updateUnreadCount();
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  }

  async function markAllAsRead() {
    try {
      await notificationsService.markAllNotificationsRead();
      // Actualizar localmente
      const now = new Date().toISOString();
      notifications.value.forEach((n: NotificationI) => {
        if (n.read_at === null) {
          n.read_at = now;
        }
      });
      unreadCount.value = 0;
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    }
  }

  function addNotificationFromFcm(notification: NotificationI) {
    // Insertar al inicio del array
    notifications.value.unshift(notification);
    updateUnreadCount();
  }

  function toggleTray() {
    trayOpen.value = !trayOpen.value;
  }

  function closeTray() {
    trayOpen.value = false;
  }

  function openTray() {
    trayOpen.value = true;
  }

  // ── Return ───────────────────────────────────────────────────────
  return {
    // state
    notifications,
    unreadCount,
    loading,
    currentPage,
    lastPage,
    total,
    trayOpen,
    // getters
    unreadNotifications,
    hasUnread,
    // actions
    fetchNotifications,
    refreshUnreadCount,
    markAsRead,
    markAllAsRead,
    addNotificationFromFcm,
    toggleTray,
    closeTray,
    openTray,
  };
});