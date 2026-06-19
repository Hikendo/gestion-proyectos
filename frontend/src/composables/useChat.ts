import { ref, computed } from 'vue';
import { useAuthStore } from '@/store/useAuthStore';
import { chatService } from '@/services/chat.service';
import type { GroupMessage, Conversation, DirectMessage } from '@/services/chat.service';

export function useGroupChat(projectId: number) {
  const authStore = useAuthStore();
  const messages = ref<GroupMessage[]>([]);
  const loading = ref(false);
  const currentPage = ref(1);
  const hasMore = ref(true);
  const sending = ref(false);

  const currentUserId = computed(() => authStore.authUser?.id);

  async function loadMessages(page: number = 1): Promise<void> {
    if (loading.value) return;
    loading.value = true;
    try {
      const response = await chatService.getGroupMessages(projectId, page);
      const newMessages = response.data.reverse();
      if (page === 1) {
        messages.value = newMessages;
      } else {
        messages.value = [...newMessages, ...messages.value];
      }
      currentPage.value = page;
      hasMore.value = response.meta.current_page < response.meta.last_page;
    } finally {
      loading.value = false;
    }
  }

  async function sendMessage(content: string): Promise<void> {
    if (sending.value || !content.trim()) return;
    sending.value = true;
    try {
      await chatService.sendGroupMessage(projectId, content);
    } finally {
      sending.value = false;
    }
  }

  function addMessage(message: GroupMessage): void {
    messages.value.push(message);
  }

  async function loadMore(): Promise<void> {
    if (!hasMore.value || loading.value) return;
    await loadMessages(currentPage.value + 1);
  }

  return {
    messages,
    loading,
    sending,
    hasMore,
    currentUserId,
    loadMessages,
    sendMessage,
    addMessage,
    loadMore,
  };
}

export function usePrivateChat(projectId: number) {
  const conversations = ref<Conversation[]>([]);
  const activeConversationId = ref<number | null>(null);
  const messages = ref<DirectMessage[]>([]);
  const loading = ref(false);
  const loadingMessages = ref(false);
  const sending = ref(false);
  const currentPage = ref(1);
  const hasMore = ref(true);

  const authStore = useAuthStore();
  const currentUserId = computed(() => authStore.authUser?.id);

  async function loadConversations(): Promise<void> {
    loading.value = true;
    try {
      const response = await chatService.getConversations(projectId);
      conversations.value = response.data;
    } finally {
      loading.value = false;
    }
  }

  async function startConversation(userId: number): Promise<number | null> {
    try {
      const response = await chatService.startConversation(projectId, userId);
      await loadConversations();
      return response.data.id;
    } catch {
      return null;
    }
  }

  async function loadMessages(conversationId: number, page: number = 1): Promise<void> {
    if (loadingMessages.value) return;
    loadingMessages.value = true;
    try {
      const response = await chatService.getDirectMessages(conversationId, page);
      const newMessages = response.data.reverse();
      if (page === 1) {
        messages.value = newMessages;
      } else {
        messages.value = [...newMessages, ...messages.value];
      }
      currentPage.value = page;
      hasMore.value = response.meta.current_page < response.meta.last_page;
    } finally {
      loadingMessages.value = false;
    }
  }

  async function openConversation(conversationId: number): Promise<void> {
    activeConversationId.value = conversationId;
    messages.value = [];
    currentPage.value = 1;
    hasMore.value = true;
    await loadMessages(conversationId);
    await chatService.markRead(conversationId);
    await loadConversations();
  }

  async function sendMessage(content: string): Promise<void> {
    if (sending.value || !content.trim() || !activeConversationId.value) return;
    sending.value = true;
    try {
      await chatService.sendDirectMessage(activeConversationId.value, content);
    } finally {
      sending.value = false;
    }
  }

  function addMessage(message: DirectMessage): void {
    if (message.conversation_id === activeConversationId.value) {
      messages.value.push(message);
      chatService.markRead(message.conversation_id);
    }
    loadConversations();
  }

  async function loadMoreMessages(): Promise<void> {
    if (!hasMore.value || loadingMessages.value || !activeConversationId.value) return;
    await loadMessages(activeConversationId.value, currentPage.value + 1);
  }

  return {
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
    loadMessages,
    sendMessage,
    addMessage,
    loadMoreMessages,
  };
}