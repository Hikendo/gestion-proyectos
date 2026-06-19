<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Notification;
use App\Services\ResendEmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job encolado que envía un email transaccional via Resend
 * como complemento a la notificación push (FCM).
 *
 * Se despacha desde AbstractNotificationService::dispatchToMany()
 * en paralelo al SendPushNotificationJob, sobre la misma cola 'notifications'.
 *
 * Si el usuario no tiene email, el job simplemente retorna sin hacer nada.
 * Si Resend falla, se registra el error pero no se bloquea al usuario ni se reintenta.
 */
class SendEmailNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El número de veces que el job puede ser reintentado.
     * Solo reintentamos 1 vez para emails (suficiente para fallos de red transitorios).
     */
    public int $tries = 1;

    /**
     * Tiempo máximo de ejecución (30 segundos es más que suficiente para una API HTTP).
     */
    public int $timeout = 30;

    /**
     * Constructor Property Promotion nativo de PHP 8.4
     */
    public function __construct(
        protected Notification $notification,
    ) {
        // Asignamos este trabajo a la misma cola dedicada de notificaciones
        $this->onQueue('notifications');
    }

    /**
     * Ejecución lógica del Job por el Worker.
     */
    public function handle(ResendEmailService $emailService): void
    {
        $user = $this->notification->user;

        // Si el usuario no tiene email, no hay nada que enviar
        if (empty($user->email)) {
            Log::channel('notifications')->debug(
                "Usuario ID {$user->id} sin email. Omitiendo envío de email para notificación ID {$this->notification->id}."
            );
            return;
        }

        Log::channel('notifications')->info(
            "Enviando email via Resend a {$user->email} para notificación ID {$this->notification->id}."
        );

        $emailService->send(
            to: $user->email,
            subject: $this->notification->title,
            body: $this->buildEmailBody($this->notification),
        );
    }

    /**
     * Construye el cuerpo HTML del email a partir de la notificación.
     */
    private function buildEmailBody(Notification $notification): string
    {
        $appName = config('app.name', 'Gestión de Proyectos');
        $appUrl = config('app.url', 'http://localhost:8000');

        $dataHtml = '';
        if (!empty($notification->data)) {
            $data = is_string($notification->data) ? json_decode($notification->data, true) : (array) $notification->data;
            $dataHtml = '<ul>';
            foreach ($data as $key => $value) {
                if (is_scalar($value)) {
                    $dataHtml .= "<li><strong>{$key}:</strong> {$value}</li>";
                }
            }
            $dataHtml .= '</ul>';
        }

        $body = $notification->body;
        $urlRoute = $notification->data['url_route'] ?? null;
        $fullUrl = $urlRoute ? rtrim($appUrl, '/') . '/' . ltrim($urlRoute, '/') : $appUrl;

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
        .footer { text-align: center; padding: 15px; color: #6b7280; font-size: 12px; }
        .button { display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
        ul { padding-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$appName}</h1>
        </div>
        <div class="content">
            <p>{$body}</p>
            {$dataHtml}
            <a href="{$fullUrl}" class="button">Ver en el panel</a>
        </div>
        <div class="footer">
            <p>Este es un correo automático de {$appName}. Por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Captura de fallas del Job.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('notifications')->error(
            "Job SendEmailNotificationJob falló para notificación ID {$this->notification->id}: {$exception->getMessage()}",
            ['notification_id' => $this->notification->id]
        );
    }
}