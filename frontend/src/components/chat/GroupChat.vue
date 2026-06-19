<template>
    <div class="group-chat d-flex flex-column" style="height: 100%">
        <!-- Messages Area -->
        <div ref="messagesContainer" class="messages-area flex-grow-1 overflow-y-auto pa-4" @scroll="onScroll">
            <div v-if="loading" class="text-center py-4">
                <v-progress-circular indeterminate color="primary" />
            </div>

            <div v-if="!loading && hasMore" class="text-center py-2">
                <v-btn variant="text" size="small" :loading="loading" @click="loadMore">
                    Cargar mensajes anteriores
                </v-btn>
            </div>

            <div v-for="msg in messages" :key="msg.id"
                :class="['message-bubble', msg.user_id === currentUserId ? 'mine' : 'theirs']">
                <div class="message-header text-caption">
                    <strong>{{ msg.user_name }}</strong>
                    <span class="text-medium-emphasis ml-2">{{ formatTime(msg.created_at) }}</span>
                </div>
                <div class="message-content">{{ msg.content }}</div>
            </div>

            <div v-if="messages.length === 0 && !loading" class="text-center text-medium-emphasis py-8">
                <v-icon size="48" color="grey">mdi-chat-outline</v-icon>
                <p class="mt-2">No hay mensajes aún. ¡Sé el primero en escribir!</p>
            </div>
        </div>

        <!-- Input Area -->
        <div class="input-area pa-3 border-t">
            <v-text-field v-model="newMessage" placeholder="Escribe un mensaje..." variant="outlined" density="compact"
                hide-details maxlength="2000" :counter="2000" :loading="sending" :disabled="sending"
                @keydown.enter.exact.prevent="handleSend">
                <template #append>
                    <v-btn icon="mdi-send" variant="text" color="primary" :disabled="!newMessage.trim() || sending"
                        :loading="sending" @click="handleSend" />
                </template>
            </v-text-field>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useGroupChat } from '@/composables/useChat';
import { subscribeToGroupChat } from '@/plugins/echo';

const props = defineProps<{
    projectId: number;
}>();

const {
    messages,
    loading,
    sending,
    hasMore,
    currentUserId,
    loadMessages,
    sendMessage,
    addMessage,
    loadMore,
} = useGroupChat(props.projectId);

const newMessage = ref('');
const messagesContainer = ref<HTMLElement | null>(null);
let unsubscribe: (() => void) | null = null;

onMounted(async () => {
    await loadMessages(1);
    scrollToBottom();

    unsubscribe = subscribeToGroupChat(props.projectId, (message) => {
        addMessage(message);
        nextTick(() => scrollToBottom());
    });
});

onUnmounted(() => {
    unsubscribe?.();
});

watch(messages, () => {
    if (messages.value.length > 0) {
        nextTick(() => {
            // Only scroll if already near bottom
            const el = messagesContainer.value;
            if (el && el.scrollHeight - el.scrollTop - el.clientHeight < 150) {
                scrollToBottom();
            }
        });
    }
});

async function handleSend(): Promise<void> {
    const content = newMessage.value.trim();
    if (!content || sending.value) return;

    newMessage.value = '';
    await sendMessage(content);
    nextTick(() => scrollToBottom());
}

function scrollToBottom(): void {
    nextTick(() => {
        const el = messagesContainer.value;
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    });
}

function onScroll(): void {
    const el = messagesContainer.value;
    if (el && el.scrollTop === 0 && hasMore.value) {
        loadMore();
    }
}

function formatTime(isoString: string): string {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);

    if (diffMin < 1) return 'Ahora';
    if (diffMin < 60) return `Hace ${diffMin} min`;

    return date.toLocaleString('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short',
    });
}
</script>

<style scoped>
.group-chat {
    background: rgb(var(--v-theme-surface));
}

.messages-area {
    scroll-behavior: smooth;
}

.message-bubble {
    margin-bottom: 12px;
    max-width: 75%;
}

.message-bubble.mine {
    margin-left: auto;
    text-align: right;
}

.message-bubble.theirs {
    margin-right: auto;
}

.message-content {
    background: rgb(var(--v-theme-surface-variant));
    padding: 8px 14px;
    border-radius: 12px;
    display: inline-block;
    white-space: pre-wrap;
    word-break: break-word;
}

.message-bubble.mine .message-content {
    background: rgb(var(--v-theme-primary));
    color: rgb(var(--v-theme-on-primary));
}

.input-area {
    background: rgb(var(--v-theme-surface));
    border-top: 1px solid rgba(var(--v-border-color), 0.12);
}
</style>