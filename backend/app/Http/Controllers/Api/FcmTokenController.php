<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFcmTokenRequest;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->fcmTokens()->select(['id', 'platform', 'browser', 'device_name', 'last_used_at'])->get();
        return response()->json(['data' => $tokens]);
    }

    public function register(RegisterFcmTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        // La restricción UNIQUE es sobre el token a nivel global.
        // Si el token ya existe (de este usuario o de otro), lo reasignamos.
        $existing = \App\Models\FcmToken::where('token', $validated['token'])->first();

        if ($existing) {
            // Si pertenece a otro usuario, lo reasignamos al usuario actual.
            // Esto cubre el caso de cerrar sesión con un usuario y loguearse con otro
            // en el mismo navegador (mismo token FCM).
            $existing->update([
                'user_id'      => $user->id,
                'platform'     => $validated['platform'] ?? 'web',
                'browser'      => $validated['browser'],
                'device_name'  => $validated['device_name'],
                'last_used_at' => now(),
            ]);
            $token = $existing;
        } else {
            $token = $user->fcmTokens()->create([
                'token'        => $validated['token'],
                'platform'     => $validated['platform'] ?? 'web',
                'browser'      => $validated['browser'],
                'device_name'  => $validated['device_name'],
                'last_used_at' => now(),
            ]);
        }

        // ── Reintentar notificaciones pendientes ──────────────────────────
        // Cuando el usuario inicia sesión y registra su token FCM,
        // reintentamos todas las notificaciones que quedaron como 'pending'
        // porque antes no tenía tokens activos.
        $this->retryPendingNotifications($user);

        return response()->json([
            'message' => 'Token FCM registrado exitosamente.',
            'data' => $token
        ], 201);
    }

    /**
     * Reintenta el envío push de todas las notificaciones 'pending' del usuario.
     */
    private function retryPendingNotifications($user): void
    {
        $pending = $user->customNotifications()
            ->where('status', 'pending')
            ->whereNull('sent_at')
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        \Illuminate\Support\Facades\Log::channel('notifications')->info(
            "FcmTokenController: reintentando {$pending->count()} notificaciones pendientes para user {$user->id}."
        );

        foreach ($pending as $notification) {
            SendPushNotificationJob::dispatch($notification);
        }
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $request->user()->fcmTokens()->where('token', $request->input('token'))->delete();

        return response()->json(['message' => 'Token desvinculado correctamente.']);
    }
}
