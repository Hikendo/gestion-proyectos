import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage, Messaging } from 'firebase/messaging';
import axios from 'axios';
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

export async function requestNotificationPermission(): Promise<void> {
  try {
    const permission = await Notification.requestPermission();
    
    if (permission === 'granted') {
      const token = await getToken(messaging, { 
        vapidKey: 'BAPVoVnTQTp6rdXziUjTQrxt5oYZ7DKvxldXtjs9clVvwswJ_ZEiVYHhx6XHULirB7P_JNNhF1z3sI_tpxEmzmU' 
      });

      if (token) {
        await saveTokenToBackend(token);
      } else {
        console.warn('No se pudo obtener el token de inscripción FCM.');
      }
    }
  } catch (error) {
    console.error('Error al configurar las notificaciones push:', error);
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

export function listenForegroundNotifications(): void {
  onMessage(messaging, (payload) => {
    console.log('Mensaje recibido en primer plano: ', payload);
    if (payload.notification?.title && payload.notification?.body) {
      // Aquí puedes mapearlo a un componente de notificación o store visual
      alert(`${payload.notification.title}: ${payload.notification.body}`);
    }
  });
}