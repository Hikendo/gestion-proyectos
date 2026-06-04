<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede ser reintentado en caso de fallas de red.
     */
    public int $tries = 3;

    /**
     * El número de segundos que el trabajo puede ejecutarse antes de expirar.
     */
    public int $timeout = 60;

    /**
     * Constructor Property Promotion nativo de PHP 8.4
     */
    public function __construct(
        protected Notification $notification,
        protected ?string $icon = null,
        protected ?string $image = null,
        protected ?string $clickAction = null
    ) {
        // Asignamos explícitamente este trabajo a la cola dedicada de notificaciones de alta prioridad
        $this->onQueue('notifications');
    }

    /**
     * Ejecución lógica del Job por el Worker.
     */
    public function handle(FirebaseNotificationService $service): void
    {
        $user = $this->notification->user;

        Log::channel('notifications')->info("Procesando envío asíncrono para la notificación ID: {$this->notification->id}");

        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            $this->notification->update(['status' => 'failed']);
            Log::channel('notifications')->warning("El usuario ID {$user->id} no tiene tokens FCM activos registrados.");
            return;
        }

        foreach ($tokens as $token) {
            $success = $service->sendToToken(
                token: $token,
                title: $this->notification->title,
                body: $this->notification->body,
                icon: $this->icon,
                image: $this->image,
                clickAction: $this->clickAction,
                customData: $this->notification->data ?? []
            );

            if ($success) {
                $this->notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } else {
                $this->notification->update(['status' => 'failed']);
            }
        }
    }

    /**
     * Captura de fallas definitivas del Job (Horizon lo registrará automáticamente).
     */
    public function failed(Throwable $exception): void
    {
        $this->notification->update(['status' => 'failed']);
        Log::channel('notifications')->error("Job SendPushNotificationJob falló críticamente: {$exception->getMessage()}", [
            'notification_id' => $this->notification->id
        ]);
    }
}
