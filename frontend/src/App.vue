<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/store/useAppStore';
import { useThemeStore } from '@/store/useThemeStore';
import { useNotificationStore } from '@/store/useNotificationStore';
import { usePermissionStore } from '@/store/usePermissionStore';
import type { NotificationI } from '@/interfaces/NotificationI';

const appStore = useAppStore();
const notificationStore = useNotificationStore();
const permissionStore = usePermissionStore();
const { loader, snackbar } = storeToRefs(appStore);

// Apply CSS variables and sync Vuetify theme on first render
const themeStore = useThemeStore();

function handleForegroundNotification(event: CustomEvent) {
    const { title, body, data: fcmData } = event.detail || {};

    // If this is a silent permissions update, just refresh permissions
    if (fcmData?.type === 'permissions_updated') {
        permissionStore.refreshPermissions();
        return;
    }

    // Construir una NotificationI temporal
    const newNotification: NotificationI = {
        id: Date.now(), // id temporal; se refrescará al recargar del backend
        user_id: 0,
        title: title || 'Notificación',
        body: body || '',
        type: fcmData?.type || 'general',
        data: fcmData || {},
        status: 'delivered',
        sent_at: new Date().toISOString(),
        read_at: null,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
    };
    notificationStore.addNotificationFromFcm(newNotification);
}

let refreshInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    themeStore.init();
    // Escuchar notificaciones FCM en primer plano
    window.addEventListener('fcm:foreground-notification', handleForegroundNotification as EventListener);
    // Cargar contador de no leídas al iniciar
    notificationStore.refreshUnreadCount();
    // Cargar lista de notificaciones para la bandeja
    notificationStore.fetchNotifications();
    // Refrescar notificaciones cada 30 segundos
    refreshInterval = setInterval(() => {
        notificationStore.refreshUnreadCount();
        notificationStore.fetchNotifications();
    }, 30000);
});

onUnmounted(() => {
    window.removeEventListener('fcm:foreground-notification', handleForegroundNotification as EventListener);
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<template>
    <VApp>
        <!-- ── Snackbar global ─────────────────────────────────────────── -->
        <VSnackbar v-model="snackbar.show" :color="snackbar.color" location="bottom right" :timeout="3500">
            {{ snackbar.text }}
            <template #actions>
                <VBtn icon="mdi-close" variant="text" @click="snackbar.show = false" />
            </template>
        </VSnackbar>

        <!-- ── Loader global ──────────────────────────────────────────── -->
        <VOverlay :model-value="loader" class="align-center justify-center" persistent>
            <VProgressCircular indeterminate color="primary" size="64" />
        </VOverlay>

        <RouterView />
    </VApp>
</template>
