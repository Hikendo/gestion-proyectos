<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Filtra una colección de usuarios dejando únicamente aquellos que:
 *  1. NO están soft-deleted
 *  2. Superan el check de policy sobre el recurso asociado a la notificación
 *
 * Uso:
 *   $authorized = (new PolicyAwareRecipientFilter())
 *       ->filter($users, 'view', $task);
 */
final class PolicyAwareRecipientFilter
{
    /**
     * @param  Collection<int, User>  $users
     * @param  string                 $ability   Habilidad de la policy (ej: 'view')
     * @param  mixed                  $resource  Instancia del modelo
     * @return Collection<int, User>
     */
    public function filter(
        Collection $users,
        string $ability,
        mixed $resource
    ): Collection {
        $resourceClass = is_object($resource) ? $resource::class : (string) $resource;
        $resourceId    = is_object($resource) && method_exists($resource, 'getKey')
            ? $resource->getKey()
            : 'n/a';

        Log::channel('notifications')->debug(
            "PolicyAwareRecipientFilter: verificando '{$ability}' sobre {$resourceClass}#{$resourceId} " .
                "para {$users->count()} candidatos."
        );

        $authorized = $users->filter(function (User $user) use ($ability, $resource, $resourceClass, $resourceId): bool {
            // Usuarios soft-deleted no reciben notificaciones
            if ($user->trashed()) {
                Log::channel('notifications')->info(
                    "Usuario ID {$user->id} omitido: soft-deleted."
                );
                return false;
            }

            $can = Gate::forUser($user)->check($ability, $resource);

            if (!$can) {
                Log::channel('notifications')->info(
                    "Usuario ID {$user->id} omitido: no tiene '{$ability}' sobre {$resourceClass}#{$resourceId}."
                );
            }

            return $can;
        })->values();

        Log::channel('notifications')->info(
            "PolicyAwareRecipientFilter: {$authorized->count()}/{$users->count()} destinatarios autorizados."
        );

        return $authorized;
    }
}
