<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * Servicio para envío de emails transaccionales via Resend.
 *
 * Usa el paquete oficial resend/resend-laravel que provee
 * el facade Resend. La API key se toma de config/services.resend.key
 * que a su vez lee RESEND_API_KEY del .env
 */
class ResendEmailService
{
    /**
     * Envía un email individual via Resend.
     *
     * @param string      $to      Dirección de email del destinatario
     * @param string      $subject Asunto del correo
     * @param string      $body    Cuerpo del correo en HTML
     * @param string|null $from    Remitente personalizado (usa RESEND_FROM_EMAIL si es null)
     * @return bool True si el envío fue exitoso
     */
    public function send(string $to, string $subject, string $body, ?string $from = null): bool
    {
        try {
            $fromEmail = $from ?? config('services.resend.from_email', env('RESEND_FROM_EMAIL', 'noreply@tudominio.com'));

            Resend::emails()->send([
                'from'    => $fromEmail,
                'to'      => [$to],
                'subject' => $subject,
                'html'    => $body,
            ]);

            Log::channel('notifications')->info("Email enviado via Resend a {$to}: {$subject}");

            return true;
        } catch (\Throwable $e) {
            Log::channel('notifications')->error("Error enviando email via Resend a {$to}: {$e->getMessage()}", [
                'exception' => $e::class,
            ]);

            return false;
        }
    }
}