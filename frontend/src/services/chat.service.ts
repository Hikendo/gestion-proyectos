import { apiWithToken } from './http';
import type { PaginatedResponse } from './http';

export interface GroupMessage {
  id: number;
  project_id: number;
  user_id: number;
  user_name: string;
  content: string;
  created_at: string;
}

export interface Conversation {
  id: number;
  project_id: number;
  other_user: {
    id: number;
    name: string;
    email: string;
  };
  unread_count: number;
  updated_at: string;
}

export interface DirectMessage {
  id: number;
  conversation_id: number;
  user_id: number;
  user_name: string;
  content: string;
  created_at: string;
}

/**
 * Chat Service — Group & Private messaging
 */
export const chatService = {
  // ── Group Chat ────────────────────────────────────────────

  /**
   * Get paginated group messages for a project.
   */
  async getGroupMessages(projectId: number, page: number = 1): Promise<PaginatedResponse<GroupMessage>> {
    const { data } = await apiWithToken.get(`/projects/${projectId}/chat/messages`, {
      params: { page },
    });
    return data;
  },

  /**
   * Send a message to the project group chat.
   */
  async sendGroupMessage(projectId: number, content: string): Promise<{ message: string; data: GroupMessage }> {
    const { data } = await apiWithToken.post(`/projects/${projectId}/chat/messages`, { content });
    return data;
  },

  // ── Private Conversations ─────────────────────────────────

  /**
   * List all conversations for the current user in a project.
   */
  async getConversations(projectId: number): Promise<{ data: Conversation[] }> {
    const { data } = await apiWithToken.get(`/projects/${projectId}/conversations`);
    return data;
  },

  /**
   * Start (or return existing) conversation with another user.
   */
  async startConversation(projectId: number, userId: number): Promise<{ message: string; data: { id: number; project_id: number; other_user: { id: number } } }> {
    const { data } = await apiWithToken.post(`/projects/${projectId}/conversations`, { user_id: userId });
    return data;
  },

  /**
   * Get paginated messages for a private conversation.
   */
  async getDirectMessages(conversationId: number, page: number = 1): Promise<PaginatedResponse<DirectMessage>> {
    const { data } = await apiWithToken.get(`/conversations/${conversationId}/messages`, {
      params: { page },
    });
    return data;
  },

  /**
   * Send a private message.
   */
  async sendDirectMessage(conversationId: number, content: string): Promise<{ message: string; data: DirectMessage }> {
    const { data } = await apiWithToken.post(`/conversations/${conversationId}/messages`, { content });
    return data;
  },

  /**
   * Mark all unread messages in a conversation as read.
   */
  async markRead(conversationId: number): Promise<{ message: string }> {
    const { data } = await apiWithToken.post(`/conversations/${conversationId}/read`);
    return data;
  },
};