import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage, deleteToken, Messaging } from 'firebase/messaging';
import { apiWithToken } from '@/services/http';

const firebaseConfig = {
  apiKey: "AIzaSyD4m6x25OSwq4lU_J7H3KLBk26fGdjv0mg",
    authDomain: "gestionfcm-57769.firebaseapp.com",
    projectId: "gestionfcm-57769",
    storageBucket: "gestionfcm-57769.firebasestorage.app",
    messagingSenderId: "809448226767",
    appId: "1:809448226767:web:464ca2d55fd0c8f5409cba",
};

const app = initializeApp(firebaseConfig);
export const messaging: Messaging = getMessaging(app);

export async function requestNotificationPermission(): Promise<'granted' | 'denied' | 'unsupported'> {
  if (!('Notification' in window)) {
    console.warn('Este navegador no soporta notificaciones push.');
    return 'unsupported';
  }

  if (Notification.permission === 'denied') {
    console.warn('El usuario ha bloqueado las notificaciones. Debe reactivarlas manualmente en la configuración del navegador.');
    return 'denied';
  }

  try {
    const permission = await Notification.requestPermission();

    if (permission === 'granted') {
      const token = await getToken(messaging, {
        vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY || 'BAPVoVnTQTp6rdXziUjTQrxt5oYZ7DKvxldXtjs9clVvwswJ_ZEiVYHhx6XHULirB7P_JNNhF1z3sI_tpxEmzmU',
      });

      if (token) {
        await saveTokenToBackend(token);
        return 'granted';
      }

      console.warn('No se pudo obtener el token de inscripción FCM.');
    }

    return permission as 'granted' | 'denied';
  } catch (error) {
    console.error('Error al configurar las notificaciones push:', error);
    return 'denied';
  }
}

/**
 * Registra el token usando tu cliente HTTP autenticado
 */
async function saveTokenToBackend(token: string): Promise<void> {
  try {
    const userAgent = navigator.userAgent;
    let browser = 'Unknown';
    if (userAgent.includes('Chrome')) browser = 'Chrome';
    else if (userAgent.includes('Safari')) browser = 'Safari';
    else if (userAgent.includes('Firefox')) browser = 'Firefox';

    // 🚀 Usamos apiWithToken para garantizar que lleve la cabecera Authorization: Bearer <token>
    // Tu prefijo global ya gestiona el '/api/v1', así que solo apuntamos al endpoint relativo.
    await apiWithToken.post('/fcm/register-token', {
      token: token,
      platform: 'web',
      browser: browser,
      device_name: navigator.platform
    });
    
    console.log('Token FCM registrado con éxito en el servidor.');
  } catch (error) {
    console.error('Error enviando el Token FCM al servidor:', error);
  }
}

/**
 * Destruye el token FCM del navegador y lo elimina del backend.
 * Debe llamarse al cerrar sesión para evitar que otro usuario herede el mismo token.
 */
export async function deleteFcmToken(): Promise<void> {
  try {
    // 1. Obtener el token actual para eliminarlo del backend
    const currentToken = await getToken(messaging, {
      vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY || 'BAPVoVnTQTp6rdXziUjTQrxt5oYZ7DKvxldXtjs9clVvwswJ_ZEiVYHhx6XHULirB7P_JNNhF1z3sI_tpxEmzmU',
    }).catch(() => null);

    // 2. Destruir el token en Firebase (invalida la suscripción push)
    await deleteToken(messaging);
    console.log('Token FCM destruido del navegador.');

    // 3. Eliminar del backend si teníamos un token
    if (currentToken) {
      await apiWithToken.post('/fcm/remove-token', { token: currentToken }).catch((err) => {
        console.warn('No se pudo eliminar el token FCM del backend:', err);
      });
    }
  } catch (error) {
    console.error('Error al destruir el token FCM:', error);
  }
}

export function listenForegroundNotifications(): void {
  onMessage(messaging, (payload: any) => {
    console.log('Mensaje recibido en primer plano: ', payload);
    if (payload.notification?.title && payload.notification?.body) {
      // Mostrar notificación nativa del navegador en primer plano
      if (Notification.permission === 'granted') {
        const notificationOptions = {
          body: payload.notification.body,
          icon: payload.data?.icon || '/images/default-icon.png',
          image: payload.data?.image,
          tag: payload.messageId || payload.data?.message_id || 'gestion-proyectos-fcm',
          data: {
            ...payload.data,
            click_action: payload.data?.click_action || '/',
          },
          requireInteraction: true,
        } as NotificationOptions & { image?: string };
        const nativeNotification = new Notification(payload.notification.title, notificationOptions);

        nativeNotification.onclick = () => {
          nativeNotification.close();
          const url = payload.data?.click_action || '/';
          window.focus();
          window.location.href = url;
        };
      }

      // Emitimos un evento global para que la capa de UI (store/componente Toast) lo capture
      window.dispatchEvent(
        new CustomEvent('fcm:foreground-notification', {
          detail: {
            title: payload.notification.title,
            body: payload.notification.body,
            data: payload.data,
            clickAction: payload.data?.click_action,
          },
        })
      );
    }
  });
}
