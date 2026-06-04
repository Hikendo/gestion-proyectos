importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyD4m6x25OSwq4lU_J7H3KLBk26fGdjv0mg",
    authDomain: "gestionfcm-57769.firebaseapp.com",
    projectId: "gestionfcm-57769",
    storageBucket: "gestionfcm-57769.firebasestorage.app",
    messagingSenderId: "809448226767",
    appId: "1:809448226767:web:464ca2d55fd0c8f5409cba",
});

const messaging = firebase.messaging();

// Captura las notificaciones en segundo plano de manera nativa
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Recibido mensaje en background: ', payload);

    const notificationTitle = payload.notification.title || 'Nueva actualización de proyecto';
    const notificationOptions = {
        body: payload.notification.body || 'Revisa tu panel de tareas para más detalles.',
        icon: payload.data?.icon || '/images/default-icon.png',
        image: payload.data?.image,
        data: {
            click_action: payload.data?.click_action || '/'
        }
    };

    return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Manejo del click en la notificación push
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const urlToOpen = event.notification.data?.click_action || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            // Si ya hay una pestaña abierta, la redirigimos e iluminamos
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // Si no hay pestañas abiertas, abre una nueva
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});