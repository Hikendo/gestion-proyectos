<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleChanged
{
    /**
     * Verifica si el rol del usuario cambió después de que se emitió su token Sanctum.
     *
     * Si role_changed_at > token.created_at, el token se emitió con permisos
     * de un rol anterior. Devolvemos 409 con código ROLE_MISMATCH para que
     * el frontend recargue la página y obtenga los permisos correctos.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $roleChangedAt = $user->role_changed_at;

        if (!$roleChangedAt) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        if (!$token) {
            return $next($request);
        }

        // Si el rol cambió después de que el token fue creado, forzar recarga
        if ($roleChangedAt->gt($token->created_at)) {
            return response()->json([
                'status' => false,
                'code' => 'ROLE_MISMATCH',
                'message' => 'Tus permisos han sido actualizados por un administrador. Recarga la página para continuar.',
            ], 409);
        }

        return $next($request);
    }
}
