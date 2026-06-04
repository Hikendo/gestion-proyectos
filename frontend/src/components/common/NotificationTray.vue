<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/store/useNotificationStore';
import type { NotificationI } from '@/interfaces/NotificationI';

const router = useRouter();
const store = useNotificationStore();
const { trayOpen, notifications, loading, unreadNotifications } = storeToRefs(store);

const displayedNotifications = computed(() => {
    // Mostrar las últimas 5 notificaciones
    return notifications.value.slice(0, 5);
});

function handleNotificationClick(notification: NotificationI) {
    if (notification.read_at === null) {
        store.markAsRead(notification.id);
    }

    // Navegar según el tipo de notificación
    if (notification.data?.click_action) {
        const action = notification.data.click_action as string;
        if (action.startsWith('/')) {
            router.push(action);
        } else if (action.includes('projectId') && notification.data.projectId) {
            router.push({
                name: action,
                params: { projectId: notification.data.projectId as string | number },
            });
        }
    }

    store.closeTray();
}

function handleMarkAllRead() {
    store.markAllAsRead();
}

function goToAllNotifications() {
    store.closeTray();
    router.push({ name: 'notifications' });
}

function formatDate(dateString: string | null): string {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'Ahora mismo';
    if (diffMin < 60) return `Hace ${diffMin} min`;
    if (diffHrs < 24) return `Hace ${diffHrs} h`;
    if (diffDays < 7) return `Hace ${diffDays} d`;
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// ── Cerrar con Escape ─────────────────────────────────────────
function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        store.closeTray();
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', onKeydown);
}
</script>

<template>
    <Teleport to="body">
        <Transition name="tray-slide">
            <div v-if="trayOpen" class="notification-tray" @click.self="store.closeTray()">
                <VCard class="tray-card" elevation="8">
                    <!-- Header -->
                    <VCardItem class="tray-header">
                        <VCardTitle class="d-flex align-center gap-2">
                            <VIcon icon="mdi-bell-outline" />
                            Notificaciones
                            <VChip v-if="unreadNotifications.length > 0" color="error" size="small" class="ml-1">
                                {{ unreadNotifications.length }}
                            </VChip>
                        </VCardTitle>
                        <template #append>
                            <VBtn v-if="unreadNotifications.length > 0" size="small" variant="text" color="primary"
                                @click="handleMarkAllRead">
                                Marcar todas leídas
                            </VBtn>
                        </template>
                    </VCardItem>

                    <VDivider />

                    <!-- Contenido -->
                    <VCardText class="tray-content pa-0">
                        <!-- Loading -->
                        <div v-if="loading" class="d-flex justify-center pa-6">
                            <VProgressCircular indeterminate color="primary" size="24" />
                        </div>

                        <!-- Vacío -->
                        <div v-else-if="displayedNotifications.length === 0"
                            class="d-flex flex-column align-center justify-center pa-6 text-medium-emphasis">
                            <VIcon icon="mdi-bell-off-outline" size="32" class="mb-2" />
                            <span class="text-body-2">No tienes notificaciones</span>
                        </div>

                        <!-- Lista -->
                        <VList v-else density="compact" class="py-0">
                            <VListItem v-for="notification in displayedNotifications" :key="notification.id"
                                class="notification-item"
                                :class="{ 'notification-unread': notification.read_at === null }"
                                @click="handleNotificationClick(notification)">
                                <template #prepend>
                                    <VBadge v-if="notification.read_at === null" color="error" dot bordered>
                                        <VIcon
                                            :icon="notification.type === 'scheduled_event' ? 'mdi-clock-outline' : 'mdi-bell-outline'"
                                            size="20" />
                                    </VBadge>
                                    <VIcon v-else
                                        :icon="notification.type === 'scheduled_event' ? 'mdi-clock-outline' : 'mdi-bell-outline'"
                                        size="20" class="text-medium-emphasis" />
                                </template>

                                <VListItemTitle class="font-weight-medium text-body-2">
                                    {{ notification.title }}
                                </VListItemTitle>
                                <VListItemSubtitle class="text-body-3 text-medium-emphasis mt-1">
                                    {{ notification.body }}
                                </VListItemSubtitle>
                                <VListItemSubtitle class="text-caption text-disabled mt-1">
                                    {{ formatDate(notification.created_at) }}
                                </VListItemSubtitle>
                            </VListItem>
                        </VList>
                    </VCardText>

                    <VDivider />

                    <!-- Footer -->
                    <VCardActions class="justify-center pa-3">
                        <VBtn variant="text" color="primary" block @click="goToAllNotifications">
                            Ver todas las notificaciones
                        </VBtn>
                    </VCardActions>
                </VCard>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.notification-tray {
    position: fixed;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    justify-content: flex-end;
    align-items: flex-start;
    background-color: transparent;
}

.tray-card {
    margin-top: 64px;
    /* altura del AppBar */
    margin-right: 16px;
    width: 400px;
    max-width: calc(100vw - 32px);
    max-height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 12px;
}

.tray-header {
    flex-shrink: 0;
}

.tray-content {
    flex: 1 1 auto;
    overflow-y: auto;
    max-height: 400px;
}

.notification-item {
    cursor: pointer;
    transition: background-color 0.15s;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.notification-item:hover {
    background-color: rgba(var(--v-theme-primary), 0.04);
}

.notification-unread {
    background-color: rgba(var(--v-theme-primary), 0.06);
}

.text-body-3 {
    font-size: 0.85rem;
    line-height: 1.3;
}

/* Transiciones */
.tray-slide-enter-active,
.tray-slide-leave-active {
    transition: opacity 0.2s ease;
}

.tray-slide-enter-from,
.tray-slide-leave-to {
    opacity: 0;
}

.tray-slide-enter-active .tray-card,
.tray-slide-leave-active .tray-card {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.tray-slide-enter-from .tray-card {
    transform: translateY(-12px) scale(0.95);
}

.tray-slide-leave-to .tray-card {
    transform: translateY(-8px) scale(0.98);
}
</style>