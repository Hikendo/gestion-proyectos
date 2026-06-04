import { apiWithToken } from '@/services/http';
import type { NotificationI, NotificationsPaginatedResponse } from '@/interfaces/NotificationI';

export async function fetchNotifications(page = 1): Promise<NotificationsPaginatedResponse> {
  const { data } = await apiWithToken.get<NotificationsPaginatedResponse>('/notifications', {
    params: { page },
  });
  return data;
}

export async function markNotificationRead(notificationId: number): Promise<void> {
  await apiWithToken.post('/notifications/mark-read', {
    notification_id: notificationId,
  });
}

export async function markAllNotificationsRead(): Promise<void> {
  await apiWithToken.post('/notifications/mark-all-read');
}

export async function fetchUnreadCount(): Promise<number> {
  // Obtenemos la primera página y contamos las no leídas desde el meta
  // Como no hay endpoint específico de count, usamos la paginación con per_page=1
  // y revisamos el total de no leídas indirectamente. 
  // Alternativa: traemos la primera página completa y filtramos.
  const { data } = await apiWithToken.get<NotificationsPaginatedResponse>('/notifications', {
    params: { per_page: 50, page: 1 },
  });
  // Retornamos cuántas no están leídas en esa primera página
  // (asumimos que el usuario no tendrá más de 50 no leídas)
  const unread = data.data.filter((n: NotificationI) => n.read_at === null).length;
  return unread;
}