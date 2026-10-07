import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { GroupMessage, DirectMessage } from '@/services/chat.service';

// Reverb usa el protocolo de Pusher por debajo, por lo que laravel-echo
// necesita el cliente `pusher-js` en `window.Pusher` para poder instanciarse.
if (typeof window !== 'undefined') {
  window.Pusher = Pusher;
}

declare global {
  interface Window {
    Pusher: typeof Pusher;
    Echo: Echo<'reverb'>;
  }
}

let echoInstance: Echo<'reverb'> | null = null;

export function initEcho(token: string): Echo<'reverb'> {
  if (echoInstance) {
    echoInstance.disconnect();
  }

  // Conexión Reverb. En desarrollo (por defecto) usa ws://localhost:8080.
  // En producción detrás de nginx con HTTPS usa wss://DOMINIO (puerto 443),
  // controlado por las variables de build:
  //   VITE_REVERB_HOST   (ej: dominio o IP)
  //   VITE_REVERB_SCHEME (http | https)
  //   VITE_REVERB_PORT   (opcional; por defecto 8080 en http, 443 en https)
  //   VITE_REVERB_APP_KEY
  const reverbHost = import.meta.env.VITE_REVERB_HOST || 'localhost';
  const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
  const forceTLS = reverbScheme === 'https';
  const reverbPort = import.meta.env.VITE_REVERB_PORT || (forceTLS ? '443' : '8080');
  const reverbKey = import.meta.env.VITE_REVERB_APP_KEY || 'gestion_proyectos_reverb_key';

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS,
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