<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterFcmTokenRequest;
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

        // Buscar token globalmente (sin scope de usuario) para evitar
        // errores de unique-constraint cuando el token ya existe para otro usuario.
        $existing = \App\Models\FcmToken::where('token', $validated['token'])->first();

        if ($existing) {
            // Si el token ya existe (mismo u otro usuario), actualizamos
            // y reasignamos al usuario actual.
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

        return response()->json([
            'message' => 'Token FCM registrado exitosamente.',
            'data' => $token
        ], 201);
    }

    public function remove(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $request->user()->fcmTokens()->where('token', $request->input('token'))->delete();

        return response()->json(['message' => 'Token desvinculado correctamente.']);
    }
}
