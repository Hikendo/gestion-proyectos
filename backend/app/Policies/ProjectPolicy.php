<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Super-admin bypasses todo.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    /**
     * Cualquier usuario autenticado puede ver proyectos donde es owner o miembro.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('project.create');
    }

    /**
     * Solo el owner o un PM miembro del proyecto puede editar.
     * No se puede editar un proyecto cerrado.
     */
    public function update(User $user, Project $project): bool
    {
        if ($project->status->isClosed()) {
            return false;
        }

        return $user->canForProject($project, 'project.edit')
            && ($project->owner_id === $user->id
                || $user->hasProjectRole($project, 'manager'));
    }

    public function delete(User $user, Project $project): bool
    {
        // El owner siempre puede eliminar su propio proyecto
        if ($project->owner_id === $user->id) {
            return true;
        }

        // Otros necesitan permiso explícito
        return $user->can('project.delete');
    }

    public function assignMembers(User $user, Project $project): bool
    {
        return $user->canForProject($project, 'project.assign-members')
            && ($project->owner_id === $user->id
                || $user->hasProjectRole($project, 'manager'));
    }
}
