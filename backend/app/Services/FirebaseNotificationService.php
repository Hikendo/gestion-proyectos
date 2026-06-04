<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FirebaseNotificationService
{
    private string $projectId;
    private array $serviceAccount;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id');

        // Cargamos la estructura de la cuenta de servicio desde variables de entorno seguras
        $this->serviceAccount = [
            'type' => 'service_account',
            'project_id' => $this->projectId,
            'private_key_id' => config('services.firebase.private_key_id'),
            'private_key' => str_replace('\n', "\n", config('services.firebase.private_key') ?? ''),
            'client_email' => config('services.firebase.client_email'),
            'client_id' => config('services.firebase.client_id'),
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ];
    }

    /**
     * Envía una notificación push a un token FCM específico.
     */
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        ?string $icon = null,
        ?string $image = null,
        ?string $clickAction = null,
        array $customData = []
    ): bool {
        $accessToken = $this->getGoogleAccessToken();
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        // Estructura oficial requerida por la API HTTP v1
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'webpush' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => $icon ?? '/images/default-icon.png',
                        'image' => $image,
                        'click_action' => $clickAction ?? config('app.url'),
                    ],
                ],
                'data' => array_map('strval', $customData), // FCM exige que todos los valores en 'data' sean strings
            ],
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if ($response->successful()) {
            return true;
        }

        // Si Firebase responde que el token ya no es válido, lo removemos de la BD inmediatamente
        if ($response->status() === 404 || $response->status() === 410) {
            Log::channel('notifications')->warning("Token FCM inválido detectado. Eliminando de la BD.", ['token' => $token]);
            FcmToken::where('token', $token)->delete();
            return false;
        }

        Log::channel('notifications')->error("Error al enviar notificación a través de FCM HTTP v1", [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return false;
    }

    /**
     * Envía notificaciones masivas iterando sobre una colección de tokens.
     * @param array<int, string> $tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, ?string $icon = null, ?string $image = null, ?string $clickAction = null, array $customData = []): void
    {
        foreach ($tokens as $token) {
            $this->sendToToken($token, $title, $body, $icon, $image, $clickAction, $customData);
        }
    }

    /**
     * Envía notificaciones push a todos los dispositivos activos de un usuario específico.
     */
    public function sendToUser(User $user, string $title, string $body, ?string $icon = null, ?string $image = null, ?string $clickAction = null, array $customData = []): void
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();
        if (!empty($tokens)) {
            $this->sendToTokens($tokens, $title, $body, $icon, $image, $clickAction, $customData);
        }
    }

    /**
     * Obtiene el Access Token JWT de Google OAuth2 para firmar la petición HTTP v1.
     *
     * Cachea el token por 55 minutos (el JWT expira en 60 min) para evitar
     * múltiples peticiones OAuth por cada notificación enviada.
     */
    private function getGoogleAccessToken(): string
    {
        $cacheKey = 'fcm:google_access_token';

        return Cache::remember($cacheKey, now()->addMinutes(55), function (): string {
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $now = time();
            $payload = json_encode([
                'iss' => $this->serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $this->serviceAccount['token_uri'],
                'exp' => $now + 3600,
                'iat' => $now,
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signature = '';
            $success = openssl_sign(
                $base64UrlHeader . "." . $base64UrlPayload,
                $signature,
                $this->serviceAccount['private_key'],
                OPENSSL_ALGO_SHA256
            );

            if (!$success) {
                throw new RuntimeException('Error interno al firmar el token JWT de Google Cloud.');
            }

            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            $response = Http::asForm()->post($this->serviceAccount['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->json()['access_token']
                ?? throw new RuntimeException('No se pudo obtener el Token de Acceso desde Google API.');
        });
    }
}
