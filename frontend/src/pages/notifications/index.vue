<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useNotificationStore } from '@/store/useNotificationStore';
import { storeToRefs } from 'pinia';
import type { NotificationI } from '@/interfaces/NotificationI';

const store = useNotificationStore();
const { notifications, loading, currentPage, lastPage, total, unreadCount } = storeToRefs(store);

const snackbar = ref(false);
const snackbarMessage = ref('');
const snackbarColor = ref('success');

function showMessage(message: string, color = 'success') {
    snackbarMessage.value = message;
    snackbarColor.value = color;
    snackbar.value = true;
}

onMounted(async () => {
    await store.fetchNotifications(1);
});

function handlePageChange(page: number) {
    store.fetchNotifications(page);
}

function handleMarkAsRead(notification: NotificationI) {
    if (notification.read_at !== null) return;
    store.markAsRead(notification.id);
    showMessage('Notificación marcada como leída');
}

function handleMarkAllRead() {
    store.markAllAsRead();
    showMessage('Todas las notificaciones marcadas como leídas');
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '—';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'Ahora mismo';
    if (diffMin < 60) return `Hace ${diffMin} minuto(s)`;
    if (diffHrs < 24) return `Hace ${diffHrs} hora(s)`;
    if (diffDays < 7) return `Hace ${diffDays} día(s)`;
    return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getNotificationIcon(type: string): string {
    switch (type) {
        case 'scheduled_event':
            return 'ri-time-line';
        case 'task_assigned':
            return 'ri-checkbox-circle-line';
        case 'ticket_created':
            return 'ri-coupon-line';
        case 'project_update':
            return 'ri-folder-line';
        case 'mention':
            return 'ri-at-line';
        default:
            return 'ri-notification-3-line';
    }
}

function getNotificationColor(type: string): string {
    switch (type) {
        case 'scheduled_event':
            return 'warning';
        case 'task_assigned':
            return 'primary';
        case 'ticket_created':
            return 'info';
        case 'project_update':
            return 'success';
        case 'mention':
            return 'error';
        default:
            return 'grey';
    }
}
</script>

<template>
    <div class="notifications-page">
        <!-- Cabecera -->
        <div class="d-flex align-center justify-space-between mb-6">
            <div>
                <h1 class="text-h5 font-weight-bold mb-1">Notificaciones</h1>
                <p class="text-body-2 text-medium-emphasis">
                    {{ total }} notificación(es) en total ·
                    <span v-if="unreadCount > 0" class="text-error font-weight-medium">
                        {{ unreadCount }} sin leer
                    </span>
                    <span v-else class="text-success">Todas leídas</span>
                </p>
            </div>
            <VBtn v-if="unreadCount > 0" variant="outlined" color="primary" prepend-icon="ri-check-double-line"
                @click="handleMarkAllRead" :loading="loading">
                Marcar todas leídas
            </VBtn>
        </div>

        <!-- Estado vacío -->
        <VCard v-if="!loading && notifications.length === 0" class="empty-state-card">
            <VCardText class="d-flex flex-column align-center justify-center pa-12">
                <VIcon icon="ri-notification-off-line" size="64" color="grey-lighten-1" class="mb-4" />
                <span class="text-h6 text-medium-emphasis mb-1">No tienes notificaciones</span>
                <span class="text-body-2 text-disabled">
                    Las notificaciones aparecerán aquí cuando haya actividad relevante.
                </span>
            </VCardText>
        </VCard>

        <!-- Lista de notificaciones -->
        <template v-else>
            <!-- Loading skeleton -->
            <div v-if="loading" class="d-flex justify-center pa-8">
                <VProgressCircular indeterminate color="primary" size="32" />
            </div>

            <!-- Items -->
            <VCard v-else>
                <VList lines="two" class="pa-0">
                    <VListItem v-for="notification in notifications" :key="notification.id" class="notification-row"
                        :class="{ 'notification-row--unread': notification.read_at === null }">
                        <template #prepend>
                            <VAvatar :color="getNotificationColor(notification.type)" variant="tonal" size="40">
                                <VIcon :icon="getNotificationIcon(notification.type)" size="20" />
                            </VAvatar>
                        </template>

                        <VListItemTitle class="font-weight-medium">
                            {{ notification.title }}
                            <VChip v-if="notification.read_at === null" color="error" size="x-small" variant="flat"
                                class="ml-2" text="Nuevo" />
                        </VListItemTitle>
                        <VListItemSubtitle class="text-body-3 mt-1">
                            {{ notification.body }}
                        </VListItemSubtitle>

                        <template #append>
                            <div class="d-flex flex-column align-end gap-2">
                                <span class="text-caption text-disabled">{{ formatDate(notification.created_at)
                                    }}</span>
                                <VBtn v-if="notification.read_at === null" size="x-small" variant="text" color="primary"
                                    icon="ri-check-line" @click.stop="handleMarkAsRead(notification)" />
                            </div>
                        </template>
                    </VListItem>
                </VList>

                <VDivider />

                <!-- Paginación -->
                <VCardActions v-if="lastPage > 1" class="justify-center pa-4">
                    <VPagination :model-value="currentPage" :length="lastPage" :total-visible="5"
                        @update:model-value="handlePageChange" />
                </VCardActions>
            </VCard>
        </template>

        <!-- Snackbar -->
        <VSnackbar v-model="snackbar" :color="snackbarColor" timeout="3000" location="bottom right">
            {{ snackbarMessage }}
            <template #actions>
                <VBtn variant="text" @click="snackbar = false">Cerrar</VBtn>
            </template>
        </VSnackbar>
    </div>
</template>

<style scoped>
.notifications-page {
    max-width: 900px;
    margin: 0 auto;
}

.empty-state-card {
    border: 2px dashed rgba(var(--v-border-color), 0.3);
    border-radius: 12px;
}

.notification-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.06);
    padding: 12px 16px;
}

.notification-row:hover {
    background-color: rgba(var(--v-theme-primary), 0.03);
}

.notification-row--unread {
    background-color: rgba(var(--v-theme-primary), 0.05);
    border-left: 3px solid rgb(var(--v-theme-primary));
}

.notification-row--unread:hover {
    background-color: rgba(var(--v-theme-primary), 0.08);
}

.text-body-3 {
    font-size: 0.85rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>