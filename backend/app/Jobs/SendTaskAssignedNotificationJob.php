<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Task;
use App\Services\FirebaseNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTaskAssignedNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 60];
    public int $maxExceptions = 3;

    public function __construct(
        protected Task $task,
        protected ?string $icon = null,
        protected ?string $image = null,
        protected ?string $clickAction = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(FirebaseNotificationService $service): void
    {
        $assignee = $this->task->assignee;

        if (! $assignee) {
            Log::channel('notifications')->warning("Tarea {$this->task->id} sin asignado — notificación omitida.");
            return;
        }

        Log::channel('notifications')->info("Enviando notificación de tarea asignada #{$this->task->id} a usuario {$assignee->id}");

        $tokens = $assignee->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::channel('notifications')->warning("Usuario {$assignee->id} no tiene tokens FCM activos.");
            return;
        }

        $anySuccess = false;

        foreach ($tokens as $token) {
            $success = $service->sendToToken(
                token: $token,
                title: 'Nueva tarea asignada',
                body: "Se te ha asignado la tarea: {$this->task->title}",
                icon: $this->icon,
                image: $this->image,
                clickAction: $this->clickAction ?? config('app.url'),
                customData: [
                    'type' => 'task_assigned',
                    'task_id' => (string) $this->task->id,
                    'project_id' => (string) $this->task->project_id,
                ],
            );

            if ($success) {
                $anySuccess = true;
            }
        }

        Log::channel('notifications')->info(
            $anySuccess
                ? "Notificación de tarea #{$this->task->id} enviada a {$assignee->id}"
                : "Notificación de tarea #{$this->task->id} falló para {$assignee->id}"
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('notifications')->error("Job SendTaskAssignedNotificationJob falló: {$exception->getMessage()}", [
            'task_id' => $this->task->id,
        ]);
    }
}