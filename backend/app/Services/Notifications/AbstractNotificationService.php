<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Clase base para todos los servicios de notificación de dominio.
 *
 * Flujo garantizado:
 *   1. Resolver destinatarios
 *   2. Filtrar por policy (opcional)
 *   3. Persistir registro por cada destinatario
 *   4. Despachar SendPushNotificationJob → cola 'notifications' (Horizon)
 */
abstract class AbstractNotificationService
{
    public function __construct(
        protected readonly NotificationRecipientResolver $resolver,
        protected readonly PolicyAwareRecipientFilter    $policyFilter,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Contrato que cada servicio de dominio debe implementar
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tipo de notificación usado en el campo `type` de la tabla notifications.
     */
    abstract protected function notificationType(): string;

    // ─────────────────────────────────────────────────────────────────────────
    // Despacho masivo
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Persiste y despacha una notificación para cada usuario de la colección.
     *
     * @param Collection<int, User> $recipients
     * @param array<string, mixed>  $data       Payload extra que se guarda en notifications.data
     */
    protected function dispatchToMany(
        Collection $recipients,
        string     $title,
        string     $body,
        array      $data        = [],
        ?string    $clickAction = null,
        ?string    $icon        = null,
        ?string    $image       = null,
    ): void {
        if ($recipients->isEmpty()) {
            Log::channel('notifications')->info(
                "[{$this->notificationType()}] Sin destinatarios — no se despacha nada."
            );
            return;
        }

        Log::channel('notifications')->info(
            "[{$this->notificationType()}] Despachando a {$recipients->count()} destinatario(s)."
        );

        // Bulk insert para evitar N+1 en la escritura a BD
        $now    = now();
        $rows   = $recipients->map(fn(User $user) => [
            'user_id'    => $user->id,
            'title'      => $title,
            'body'       => $body,
            'type'       => $this->notificationType(),
            'data'       => json_encode($data),
            'status'     => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Insertar todos los registros de una sola vez
        Notification::insert($rows);

        // Recuperar los IDs recién insertados para asociar el Job
        $insertedNotifications = Notification::where('status', 'pending')
            ->where('type', $this->notificationType())
            ->where('created_at', $now)
            ->whereIn('user_id', $recipients->pluck('id'))
            ->get()
            ->keyBy('user_id');

        foreach ($recipients as $user) {
            $notification = $insertedNotifications->get($user->id);

            if (!$notification) {
                Log::channel('notifications')->warning(
                    "[{$this->notificationType()}] No se encontró la notificación persistida para user_id {$user->id}."
                );
                continue;
            }

            SendPushNotificationJob::dispatch(
                notification: $notification,
                clickAction: $clickAction,
                icon: $icon,
                image: $image,
            );

            Log::channel('notifications')->debug(
                "[{$this->notificationType()}] Job despachado para user_id {$user->id}, notification_id {$notification->id}."
            );
        }
    }

    /**
     * Persiste y despacha para un único destinatario.
     */
    protected function dispatchToUser(
        User    $user,
        string  $title,
        string  $body,
        array   $data        = [],
        ?string $clickAction = null,
        ?string $icon        = null,
        ?string $image       = null,
    ): void {
        $this->dispatchToMany(
            recipients: collect([$user]),
            title: $title,
            body: $body,
            data: $data,
            clickAction: $clickAction,
            icon: $icon,
            image: $image,
        );
    }
}
