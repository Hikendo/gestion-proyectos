<template>
    <div class="private-chat d-flex" style="height: 100%">
        <!-- Conversation List -->
        <div class="conversation-list border-r" style="width: 300px; min-width: 250px">
            <v-list lines="two" density="compact">
                <v-list-subheader>Conversaciones</v-list-subheader>

                <v-list-item v-for="conv in conversations" :key="conv.id" :active="activeConversationId === conv.id"
                    :title="conv.other_user.name" :subtitle="conv.other_user.email" @click="openConversation(conv.id)">
                    <template #prepend>
                        <v-avatar color="primary" size="36">
                            <span class="text-white">{{ conv.other_user.name.charAt(0).toUpperCase() }}</span>
                        </v-avatar>
                    </template>
                    <template #append>
                        <v-badge v-if="conv.unread_count > 0" color="error" :content="conv.unread_count" inline />
                    </template>
                </v-list-item>

                <v-list-item v-if="conversations.length === 0 && !loading" title="Sin conversaciones"
                    subtitle="Inicia una desde la pestaña de miembros" />
            </v-list>

            <!-- Start New Conversation -->
            <div class="pa-3 border-t">
                <v-btn variant="outlined" color="primary" block size="small" @click="showMemberDialog = true">
                    <v-icon start>mdi-plus</v-icon>
                    Nueva conversación
                </v-btn>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area flex-grow-1 d-flex flex-column">
            <template v-if="activeConversationId">
                <!-- Messages -->
                <div ref="messagesContainer" class="messages-area flex-grow-1 overflow-y-auto pa-4" @scroll="onScroll">
                    <div v-if="loadingMessages" class="text-center py-4">
                        <v-progress-circular indeterminate color="primary" />
                    </div>

                    <div v-if="!loadingMessages && hasMore" class="text-center py-2">
                        <v-btn variant="text" size="small" :loading="loadingMessages" @click="loadMoreMessages">
                            Cargar anteriores
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

                    <div v-if="messages.length === 0 && !loadingMessages" class="text-center text-medium-emphasis py-8">
                        <v-icon size="48" color="grey">mdi-message-outline</v-icon>
                        <p class="mt-2">No hay mensajes aún</p>
                    </div>
                </div>

                <!-- Input -->
                <div class="input-area pa-3 border-t">
                    <v-text-field v-model="newMessage" placeholder="Escribe un mensaje..." variant="outlined"
                        density="compact" hide-details maxlength="2000" :loading="sending" :disabled="sending"
                        @keydown.enter.exact.prevent="handleSend">
                        <template #append>
                            <v-btn icon="mdi-send" variant="text" color="primary"
                                :disabled="!newMessage.trim() || sending" :loading="sending" @click="handleSend" />
                        </template>
                    </v-text-field>
                </div>
            </template>
            <template v-else>
                <div class="d-flex align-center justify-center flex-grow-1 text-medium-emphasis">
                    <div class="text-center">
                        <v-icon size="64" color="grey">mdi-forum-outline</v-icon>
                        <p class="mt-2">Selecciona una conversación</p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Member Selection Dialog -->
        <v-dialog v-model="showMemberDialog" max-width="400">
            <v-card title="Nueva conversación">
                <v-card-text>
                    <v-select v-model="selectedMemberId" :items="projectMembers" item-title="name" item-value="id"
                        label="Selecciona un miembro" variant="outlined" density="compact" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showMemberDialog = false">Cancelar</v-btn>
                    <v-btn variant="tonal" color="primary" :disabled="!selectedMemberId" @click="startNewConversation">
                        Iniciar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePrivateChat } from '@/composables/useChat';
import { subscribeToConversation } from '@/plugins/echo';
import { useAuthStore } from '@/store/useAuthStore';

const props = defineProps<{
    projectId: number;
    projectMembers: Array<{ id: number; name: string }>;
}>();

const authStore = useAuthStore();

const {
    conversations,
    activeConversationId,
    messages,
    loading,
    loadingMessages,
    sending,
    hasMore,
    currentUserId,
    loadConversations,
    startConversation,
    openConversation,
    sendMessage,
    addMessage,
    loadMoreMessages,
} = usePrivateChat(props.projectId);

const newMessage = ref('');
const messagesContainer = ref<HTMLElement | null>(null);
const showMemberDialog = ref(false);
const selectedMemberId = ref<number | null>(null);
let unsubscribe: (() => void) | null = null;

onMounted(async () => {
    await loadConversations();
});

onUnmounted(() => {
    unsubscribe?.();
});

watch(activeConversationId, (newId, oldId) => {
    if (oldId) {
        unsubscribe?.();
    }
    if (newId) {
        unsubscribe = subscribeToConversation(newId, (message) => {
            addMessage(message);
            nextTick(scrollToBottom);
        });
    }
});

watch(messages, () => {
    nextTick(() => {
        const el = messagesContainer.value;
        if (el && el.scrollHeight - el.scrollTop - el.clientHeight < 150) {
            scrollToBottom();
        }
    });
});

async function handleSend(): Promise<void> {
    const content = newMessage.value.trim();
    if (!content || sending.value) return;
    newMessage.value = '';
    await sendMessage(content);
    nextTick(scrollToBottom);
}

function scrollToBottom(): void {
    const el = messagesContainer.value;
    if (el) el.scrollTop = el.scrollHeight;
}

function onScroll(): void {
    const el = messagesContainer.value;
    if (el && el.scrollTop === 0 && hasMore.value) {
        loadMoreMessages();
    }
}

async function startNewConversation(): Promise<void> {
    if (!selectedMemberId.value) return;
    const id = await startConversation(selectedMemberId.value);
    if (id) {
        showMemberDialog.value = false;
        selectedMemberId.value = null;
        await openConversation(id);
    }
}

function formatTime(isoString: string): string {
    const date = new Date(isoString);
    const now = new Date();
    const diffMin = Math.floor((now.getTime() - date.getTime()) / 60000);
    if (diffMin < 1) return 'Ahora';
    if (diffMin < 60) return `Hace ${diffMin} min`;
    return date.toLocaleString('es-MX', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' });
}
</script>

<style scoped>
.conversation-list {
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