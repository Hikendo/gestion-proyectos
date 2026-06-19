import Echo from 'laravel-echo';
import type { GroupMessage, DirectMessage } from '@/services/chat.service';

// Reverb uses the Pusher protocol underneath, so we use the pusher-js client
// We'll import Pusher globally in index.html or setup it here
declare global {
  interface Window {
    Pusher: any;
    Echo: Echo<'reverb'>;
  }
}

let echoInstance: Echo<'reverb'> | null = null;

export function initEcho(token: string): Echo<'reverb'> {
  if (echoInstance) {
    echoInstance.disconnect();
  }

  const reverbHost = import.meta.env.VITE_REVERB_HOST || 'localhost';
  const reverbPort = import.meta.env.VITE_REVERB_PORT || '8080';
  const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'gestion_proyectos_reverb_key';

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL || '/api/v1'}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });

  window.Echo = echoInstance;

  return echoInstance;
}

export function getEcho(): Echo<'reverb'> | null {
  return echoInstance;
}

export function disconnectEcho(): void {
  if (echoInstance) {
    echoInstance.disconnect();
    echoInstance = null;
  }
}

/**
 * Subscribe to the project group chat channel.
 */
export function subscribeToGroupChat(
  projectId: number,
  onMessage: (message: GroupMessage) => void,
): () => void {
  const echo = getEcho();
  if (!echo) return () => {};

  const channel = echo.private(`project.${projectId}`);
  channel.listen('.message.sent', (event: any) => {
    onMessage({
      id: event.id,
      project_id: event.project_id,
      user_id: event.user_id,
      user_name: event.user_name,
      content: event.content,
      created_at: event.created_at,
    });
  });

  return () => {
    echo.leave(`project.${projectId}`);
  };
}

/**
 * Subscribe to a private conversation channel.
 */
export function subscribeToConversation(
  conversationId: number,
  onMessage: (message: DirectMessage) => void,
): () => void {
  const echo = getEcho();
  if (!echo) return () => {};

  const channel = echo.private(`conversation.${conversationId}`);
  channel.listen('.direct-message.sent', (event: any) => {
    onMessage({
      id: event.id,
      conversation_id: event.conversation_id,
      user_id: event.user_id,
      user_name: event.user_name,
      content: event.content,
      created_at: event.created_at,
    });
  });

  return () => {
    echo.leave(`conversation.${conversationId}`);
  };
}