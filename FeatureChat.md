# Solicitud de Funcionalidad: Chat Grupal y Privado por Proyecto con Laravel Reverb y Notificaciones FCM

**Título:** Chat Grupal + Chats Privados en Tiempo Real (WebSockets) y Migración de Notificaciones a FCM  
**Fecha:** 2026-06-19  
**Solicitante:** [Nombre / Área]  
**Prioridad:** Alta  
**Proyecto:** Módulo de Gestión de Proyectos  

---

## 1. Descripción General

Se requiere implementar un sistema de mensajería en tiempo real dentro de cada proyecto, compuesto por:

- Un **chat grupal** donde participan automáticamente todos los miembros del proyecto.
- **Chats privados** uno a uno entre cualquier par de miembros del mismo proyecto.

La comunicación instantánea se logrará mediante **Laravel Reverb** (servidor WebSocket oficial de Laravel) integrado con el frontend Vue a través de Laravel Echo.  
Paralelamente, se modificará el sistema de notificaciones para utilizar **Firebase Cloud Messaging (FCM)** como disparador de refresco de la bandeja y de los mensajes cuando la aplicación no está en primer plano.

---

## 2. Motivación

- **Problema actual:** El frontend podría estar realizando polling o depender de recargas manuales para obtener nuevos mensajes o notificaciones, generando tráfico innecesario y una experiencia pobre.
- **Chat grupal:** Los equipos necesitan un espacio común de discusión sobre el proyecto, accesible a todos sus miembros.
- **Chats privados:** A menudo surgen conversaciones que solo competen a dos personas (un PM y un developer, un QA y un dev, etc.). Permitir mensajes directos evita saturar el chat grupal y agiliza la coordinación.
- **Notificaciones push:** Al integrar FCM, el servidor envía una notificación solo cuando hay contenido nuevo, y el frontend reacciona realizando una petición puntual, eliminando el sondeo constante.

---

## 3. Alcance

- **Incluye:**
  - Backend Laravel: modelos `ProjectMessage` (grupal) y `DirectMessage` (privado), endpoints REST para historial y envío, eventos de broadcasting `MessageSent` y `DirectMessageSent`.
  - Backend Laravel: configuración de Laravel Reverb como servidor WebSocket.
  - Backend Laravel: envío de notificaciones push FCM para nuevos mensajes (tanto grupales como privados) cuando el destinatario no está conectado.
  - Frontend Vue:
    - Sala de chat grupal en la vista del proyecto.
    - Lista de conversaciones privadas y ventana de chat uno a uno.
    - Conexión WebSocket vía Laravel Echo para ambos tipos de chat.
    - Integración del SDK de Firebase para refrescar bandeja y chats al recibir notificaciones push.
  - Docker: nuevo servicio `reverb` en `docker-compose.yml`.
- **No incluye:** Chats entre usuarios de distintos proyectos, videollamadas, envío de archivos (evaluar en futuras iteraciones).

---

## 4. Requisitos Funcionales

### 4.1 Chat Grupal por Proyecto

| ID | Requisito |
|----|-----------|
| **CH‑01** | Cada proyecto tendrá una única sala de chat donde participan **todos sus miembros** (sin distinción de rol). El acceso se hereda de la membresía. |
| **CH‑02** | La interfaz de chat grupal estará en una pestaña/sección del proyecto llamada "Chat del equipo". |
| **CH‑03** | Un usuario puede enviar mensajes de texto (máx. 2000 caracteres). El mensaje se asocia al proyecto y al remitente. |
| **CH‑04** | El historial se carga al entrar, mostrando los últimos 50 mensajes. Scroll infinito hacia arriba para recuperar mensajes más antiguos (paginación vía API). |
| **CH‑05** | Los mensajes se ordenan cronológicamente (el más reciente abajo). Los nuevos aparecen automáticamente al final gracias al WebSocket. |
| **CH‑06** | Cada mensaje muestra: nombre, avatar (si existe), timestamp relativo ("hace 2 min"), contenido. |
| **CH‑07** | Los mensajes son persistentes. No pueden editarse ni eliminarse (excepto por Super Admin o PM con permiso, en fase posterior). |

### 4.2 Chat Privado entre Miembros

| ID | Requisito |
|----|-----------|
| **CP‑01** | Cualquier miembro de un proyecto puede iniciar una conversación privada con otro miembro del **mismo proyecto**. |
| **CP‑02** | El usuario verá una lista de "Conversaciones" (chats privados existentes) y podrá buscar/iniciar una nueva desde un listado de miembros del proyecto. |
| **CP‑03** | Cada conversación privada es única entre dos usuarios (no se pueden duplicar). Si ya existe una conversación entre A y B, al intentar iniciar otra se abrirá la existente. |
| **CP‑04** | Los mensajes privados son visibles **solo** para los dos participantes. Ni el PM ni el Super Admin pueden leerlos (a menos que se implemente un permiso especial de auditoría, fuera de esta fase). |
| **CP‑05** | El historial de una conversación se carga con paginación, similar al chat grupal (últimos 50 mensajes, scroll infinito). |
| **CP‑06** | Los mensajes privados se transmiten en tiempo real vía WebSocket a los dos participantes. |
| **CP‑07** | Se mostrará un indicador de no leídos (contador) en la lista de conversaciones. |
| **CP‑08** | Los mensajes privados son persistentes y no se pueden editar ni borrar. |
| **CP‑09** | (Opcional) Indicador de "escribiendo..." en la conversación. |

### 4.3 Notificaciones Push con FCM

| ID | Requisito |
|----|-----------|
| **NF‑01** | Se modificará el sistema de notificaciones para que, además de guardarlas en BD, **envíe una notificación push a través de FCM** al destinatario. |
| **NF‑02** | La notificación FCM incluirá un `data payload` con: `type` (ej. `new_group_message`, `new_private_message`, `task_completed`), `project_id`, `resource_id` (y `chat_id` para privados). |
| **NF‑03** | Al recibir una notificación push, el frontend disparará una petición para refrescar la bandeja de notificaciones y, si corresponde, la vista del chat activo (grupal o privado). |
| **NF‑04** | Al cargar/recargar la página, el frontend obtiene el estado actual de notificaciones mediante la API REST. |
| **NF‑05** | El sistema gestionará los tokens FCM por usuario y dispositivo. |
| **NF‑06** | Se respetarán las preferencias de notificación del usuario. |
| **NF‑07** | El contenido del mensaje **no viajará en el payload de FCM**; solo un aviso genérico. El frontend autenticado obtendrá el contenido vía API. |
| **NF‑08** | La bandeja interna sigue siendo la fuente de verdad; FCM solo actúa como disparador. |

---

## 5. Criterios de Aceptación

### Chat Grupal

1. **Acceso restringido:** Un usuario no miembro no puede acceder (API 403, pestaña oculta).
2. **Tiempo real:** Mensaje enviado → aparece inmediatamente en todos los miembros conectados.
3. **Historial:** Scroll infinito funciona sin perder nuevos mensajes.
4. **Persistencia:** Mensajes sobreviven a recargas.

### Chat Privado

1. **Inicio de conversación:** Usuario A selecciona a Usuario B del mismo proyecto → se crea/abre conversación privada.
2. **Exclusividad:** Solo A y B ven los mensajes; un tercer miembro (aunque sea PM) no puede acceder a ellos.
3. **No duplicación:** Intentar crear una conversación ya existente abre la existente.
4. **Tiempo real:** Mensaje privado → ambos participantes lo ven al instante.
5. **No leídos:** Si un usuario no está en la conversación, el contador de no leídos se incrementa y se refleja en la lista.
6. **Notificación push:** Cuando un participante está offline, recibe una notificación push “Nuevo mensaje privado de [nombre]” (sin contenido). Al abrirla, se carga la conversación correspondiente.

### Notificaciones FCM

1. **Entrega push:** Al enviar un mensaje (grupal o privado), los destinatarios offline reciben push.
2. **Refresco automático:** Al hacer clic en la notificación, se actualiza la bandeja y se redirige a la conversación adecuada.
3. **Sin polling:** No existen intervalos de sondeo.
4. **Actualización al recargar:** Estado correcto al recargar.
5. **Preferencias:** Desactivar notificaciones de chat detiene los push, pero no afecta la bandeja interna.

---

## 6. Arquitectura Técnica Propuesta

### 6.1 Stack en Docker

Se añadirá un contenedor de **Laravel Reverb** al `docker-compose.yml`:

```yaml
  reverb:
    image: laravel/reverb:latest
    container_name: reverb
    restart: unless-stopped
    ports:
      - "8080:8080"
    environment:
      - REVERB_APP_ID=${REVERB_APP_ID}
      - REVERB_APP_KEY=${REVERB_APP_KEY}
      - REVERB_APP_SECRET=${REVERB_APP_SECRET}
      - REVERB_HOST=0.0.0.0
      - REVERB_PORT=8080
      - REVERB_SCHEME=http
    networks:
      - app-network
