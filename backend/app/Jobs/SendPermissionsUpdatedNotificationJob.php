<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPermissionsUpdatedNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];
    public int $maxExceptions = 3;

    public function __construct(
        protected User $user,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(FirebaseNotificationService $service): void
    {
        Log::channel('notifications')->info("Enviando notificación silenciosa de permisos actualizados al usuario {$this->user->id}");

        $tokens = $this->user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::channel('notifications')->info("Usuario {$this->user->id} no tiene tokens FCM — omitiendo notificación.");
            return;
        }

        foreach ($tokens as $token) {
            $service->sendToToken(
                token: $token,
                title: '',
                body: '',
                customData: ['type' => 'permissions_updated'],
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('notifications')->error("Job SendPermissionsUpdatedNotificationJob falló: {$exception->getMessage()}", [
            'user_id' => $this->user->id,
        ]);
    }
}