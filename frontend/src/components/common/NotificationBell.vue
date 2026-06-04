<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useNotificationStore } from '@/store/useNotificationStore';

const router = useRouter();
const store = useNotificationStore();
const { unreadCount, hasUnread, trayOpen } = storeToRefs(store);

onMounted(() => {
    store.fetchNotifications(1);
});

function handleClick() {
    store.toggleTray();
}

function goToAllNotifications() {
    store.closeTray();
    router.push({ name: 'notifications' });
}
</script>

<template>
    <div class="notification-bell-wrapper">
        <VBadge :model-value="hasUnread" color="error" dot bordered overlap="circle">
            <VBtn icon="mdi-bell-outline" variant="text" :color="trayOpen ? 'primary' : undefined"
                @click="handleClick" />
        </VBadge>
        <span v-if="unreadCount > 0" class="unread-badge text-caption font-weight-bold">
            {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
    </div>
</template>

<style scoped>
.notification-bell-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
}

.unread-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background-color: rgb(var(--v-theme-error));
    color: rgb(var(--v-theme-on-error));
    border-radius: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    font-size: 10px;
    line-height: 1;
    pointer-events: none;
}
</style>