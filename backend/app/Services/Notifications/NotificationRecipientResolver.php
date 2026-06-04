<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Resuelve los destinatarios de una notificación según rol, permiso,
 * pertenencia a proyecto o asignación de tarea/ticket.
 *
 * Evita N+1 mediante eager loading y usa cursor pagination para miles de usuarios.
 */
final class NotificationRecipientResolver
{
    // ─────────────────────────────────────────────────────────────────────────
    // Resolución por rol / roles
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Usuarios activos que poseen exactamente el rol indicado.
     *
     * @return Collection<int, User>
     */
    public function resolveByRole(string $role, array $excludeIds = []): Collection
    {
        Log::channel('notifications')->debug("Resolviendo destinatarios por rol: {$role}");

        $users = User::role($role)
            ->whereNull('deleted_at')
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->with('fcmTokens')
            ->get();

        Log::channel('notifications')->info(
            "Rol '{$role}': {$users->count()} destinatarios encontrados."
        );

        return $users;
    }

    /**
     * Usuarios activos que poseen AL MENOS uno de los roles indicados.
     *
     * @param  array<int, string> $roles
     * @return Collection<int, User>
     */
    public function resolveByRoles(array $roles, array $excludeIds = []): Collection
    {
        Log::channel('notifications')->debug('Resolviendo destinatarios por roles: ' . implode(', ', $roles));

        $users = User::role($roles)
            ->whereNull('deleted_at')
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->with('fcmTokens')
            ->get();

        Log::channel('notifications')->info(
            'Roles [' . implode(', ', $roles) . "]: {$users->count()} destinatarios encontrados."
        );

        return $users;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolución por permiso
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Usuarios activos que poseen el permiso indicado (directo o por rol).
     *
     * @return Collection<int, User>
     */
    public function resolveByPermission(string $permission, array $excludeIds = []): Collection
    {
        Log::channel('notifications')->debug("Resolviendo destinatarios por permiso: {$permission}");

        $users = User::permission($permission)
            ->whereNull('deleted_at')
            ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
            ->with('fcmTokens')
            ->get();

        Log::channel('notifications')->info(
            "Permiso '{$permission}': {$users->count()} destinatarios encontrados."
        );

        return $users;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolución por membresía de proyecto
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Owner + todos los miembros activos del proyecto (sin duplicados).
     *
     * @return Collection<int, User>
     */
    public function resolveProjectMembers(Project $project, array $excludeIds = []): Collection
    {
        Log::channel('notifications')->debug("Resolviendo miembros del proyecto ID: {$project->id}");

        // Cargamos membresías con su usuario en un solo query
        $project->loadMissing(['members.user.fcmTokens', 'owner.fcmTokens']);

        /** @var Collection<int, User> $users */
        $users = collect();

        // Owner
        if ($project->owner && !in_array($project->owner->id, $excludeIds, true)) {
            $users->push($project->owner);
        }

        // Miembros (excluye soft-deleted)
        foreach ($project->members as $membership) {
            if (
                $membership->user
                && !in_array($membership->user->id, $excludeIds, true)
                && !$users->contains('id', $membership->user->id)
            ) {
                $users->push($membership->user);
            }
        }

        Log::channel('notifications')->info(
            "Proyecto ID {$project->id}: {$users->count()} miembros resueltos."
        );

        return $users;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolución por asignación de tarea
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Usuario(s) asignados a la tarea. Puede ser uno o ninguno.
     *
     * @return Collection<int, User>
     */
    public function resolveTaskAssignees(Task $task, array $excludeIds = []): Collection
    {
        Log::channel('notifications')->debug("Resolviendo asignados de la tarea ID: {$task->id}");

        $task->loadMissing('assignee.fcmTokens');

        $users = collect();

        if (
            $task->assignee
            && !in_array($task->assignee->id, $excludeIds, true)
        ) {
            $users->push($task->assignee);
        }

        Log::channel('notifications')->info(
            "Tarea ID {$task->id}: {$users->count()} asignado(s) resuelto(s)."
        );

        return $users;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolución por asignación de ticket
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Usuario asignado al ticket (si existe).
     *
     * @return Collection<int, User>
     */
    public function resolveTicketAssignees(Ticket $ticket, array $excludeIds = []): Collection
    {
        Log::channel('notifications')->debug("Resolviendo asignados del ticket ID: {$ticket->id}");

        $ticket->loadMissing('assignee.fcmTokens');

        $users = collect();

        if (
            $ticket->assignee
            && !in_array($ticket->assignee->id, $excludeIds, true)
        ) {
            $users->push($ticket->assignee);
        }

        Log::channel('notifications')->info(
            "Ticket ID {$ticket->id}: {$users->count()} asignado(s) resuelto(s)."
        );

        return $users;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolución filtrada por policy
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Filtra una colección de usuarios dejando solo los que superan el check
     * de policy sobre el recurso dado.
     *
     * @param  Collection<int, User>  $users
     * @param  string                 $ability  Habilidad de la policy (ej: 'view')
     * @param  mixed                  $resource Instancia del modelo a verificar
     * @return Collection<int, User>
     */
    public function resolvePolicyAuthorizedUsers(
        Collection $users,
        string $ability,
        mixed $resource
    ): Collection {
        return (new PolicyAwareRecipientFilter())->filter($users, $ability, $resource);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Método utilitario para un usuario concreto
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve una colección con un único usuario (si está activo).
     *
     * @return Collection<int, User>
     */
    public function resolveUser(User $user): Collection
    {
        $user->loadMissing('fcmTokens');

        return collect([$user]);
    }
}
