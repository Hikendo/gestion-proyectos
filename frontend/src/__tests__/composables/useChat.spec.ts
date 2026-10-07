import { describe, it, expect, vi, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useGroupChat, usePrivateChat } from '@/composables/useChat';
import { chatService } from '@/services/chat.service';
import type { GroupMessage, DirectMessage } from '@/services/chat.service';

// Echo imports pusher-js (browser APIs). No lo necesitamos para el composable.
vi.mock('@/plugins/echo', () => ({
    initEcho: vi.fn(),
    disconnectEcho: vi.fn(),
    getEcho: vi.fn(() => null),
    subscribeToGroupChat: vi.fn(() => () => {}),
    subscribeToConversation: vi.fn(() => () => {}),
}));

const paginated = <T,>(data: T[]) => ({
    data,
    links: { first: null, last: null, prev: null, next: null },
    meta: {
        current_page: 1,
        from: 1,
        last_page: 1,
        links: [],
        path: '/',
        per_page: 50,
        to: data.length,
        total: data.length,
    },
});

describe('useGroupChat', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('loadMessages lee la paginación estándar { data, meta }', async () => {
        const messages: GroupMessage[] = [
            { id: 2, project_id: 1, user_id: 1, user_name: 'B', content: 'segundo', created_at: '2026-01-02T00:00:00Z' },
            { id: 1, project_id: 1, user_id: 1, user_name: 'A', content: 'primero', created_at: '2026-01-01T00:00:00Z' },
        ];
        vi.spyOn(chatService, 'getGroupMessages').mockResolvedValueOnce(paginated(messages));

        const { messages: list, hasMore, loadMessages } = useGroupChat(1);
        await loadMessages(1);

        expect(list.value).toHaveLength(2);
        // Se invierte el orden (desc → asc) para renderizar cronológicamente.
        expect(list.value[0].id).toBe(1);
        expect(list.value[1].id).toBe(2);
        expect(hasMore.value).toBe(false);
    });

    it('sendMessage inserta localmente el mensaje devuelto por el POST', async () => {
        vi.spyOn(chatService, 'sendGroupMessage').mockResolvedValueOnce({
            message: 'ok',
            data: { id: 99, project_id: 1, user_id: 1, user_name: 'Yo', content: 'hola', created_at: '2026-01-03T00:00:00Z' },
        });

        const { messages, sendMessage } = useGroupChat(1);
        await sendMessage('hola');

        expect(messages.value).toHaveLength(1);
        expect(messages.value[0].id).toBe(99);
        expect(messages.value[0].content).toBe('hola');
    });

    it('addMessage deduplica mensajes por id', () => {
        const { messages, addMessage } = useGroupChat(1);
        const msg: GroupMessage = { id: 5, project_id: 1, user_id: 1, user_name: 'A', content: 'x', created_at: '2026-01-01T00:00:00Z' };

        addMessage(msg);
        addMessage(msg);

        expect(messages.value).toHaveLength(1);
    });
});

describe('usePrivateChat', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('sendMessage inserta localmente el mensaje directo devuelto por el POST', async () => {
        vi.spyOn(chatService, 'sendDirectMessage').mockResolvedValueOnce({
            message: 'ok',
            data: { id: 7, conversation_id: 3, user_id: 1, user_name: 'Yo', content: 'hola', created_at: '2026-01-03T00:00:00Z' },
        });
        vi.spyOn(chatService, 'markRead').mockResolvedValue({ message: 'ok' });
        vi.spyOn(chatService, 'getConversations').mockResolvedValue({ data: [] });

        const { activeConversationId, messages, sendMessage } = usePrivateChat(1);
        activeConversationId.value = 3;

        await sendMessage('hola');

        expect(messages.value).toHaveLength(1);
        expect(messages.value[0].id).toBe(7);
    });

    it('addMessage deduplica mensajes directos por id', () => {
        const { activeConversationId, messages, addMessage } = usePrivateChat(1);
        activeConversationId.value = 3;

        const msg: DirectMessage = { id: 8, conversation_id: 3, user_id: 1, user_name: 'A', content: 'x', created_at: '2026-01-01T00:00:00Z' };
        addMessage(msg);
        addMessage(msg);

        expect(messages.value).toHaveLength(1);
    });
});
