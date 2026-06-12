<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RoleChanged;
use App\Jobs\SendPermissionsUpdatedNotificationJob;
use Illuminate\Support\Facades\Log;

class InvalidateUserSession
{
    /**
     * Handle the RoleChanged event.
     *
     * 1. Actualiza el timestamp role_changed_at en el usuario.
     * 2. Envía notificación FCM silenciosa para que el frontend refresque permisos.
     *
     * No revocamos tokens aquí — el middleware CheckRoleChanged
     * detecta el mismatch y devuelve 409 para forzar recarga.
     */
    public function handle(RoleChanged $event): void
    {
        $user = $event->user;

        // Registrar el momento del cambio de rol
        $user->update(['role_changed_at' => now()]);

        // Limpiar caché de permisos Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Notificar al frontend vía FCM (ya implementado)
        SendPermissionsUpdatedNotificationJob::dispatch($user);

        Log::info("Rol cambiado para usuario {$user->id}", [
            'old_role' => $event->oldRole,
            'new_role' => $event->newRole,
        ]);
    }
}
