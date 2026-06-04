<?php

declare(strict_types=1);

namespace App\Notifications\Domain;

use App\Models\Notification;
use App\Models\User;
use App\Jobs\SendPushNotificationJob;

class TaskAssignedNotification
{
    /**
     * Lanza la secuencia lógica: persiste en BD → despacha a la cola de Redis.
     */
    public static function send(User $recipient, string $taskTitle, int $taskId): void
    {
        // 1. Persistencia local en la base de datos para el historial del panel
        $notification = Notification::create([
            'user_id' => $recipient->id,
            'title' => 'Nueva Tarea Asignada',
            'body' => "Se te ha asignado la tarea: \"{$taskTitle}\". Revisa los detalles en tu tablero.",
            'type' => 'task_assigned',
            'data' => [
                'task_id' => $taskId,
                'url_route' => "/tasks/{$taskId}"
            ],
            'status' => 'pending',
        ]);

        // 2. Despacho inmediato a la cola administrada por Horizon
        SendPushNotificationJob::dispatch(
            notification: $notification,
            clickAction: config('app.url') . "/projects"
        );
    }
}
