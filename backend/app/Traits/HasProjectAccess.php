<?php

namespace App\Traits;

use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\User;

/**
 * Verificación de acceso a proyecto reutilizable en Services y Controllers.
 */
trait HasProjectAccess
{
    public function assertCanAccessProject(User $user, Project $project): void
    {
        if ($user->hasRole('super-admin')) {
            return;
        }

        $hasAccess = $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();

        if (! $hasAccess) {
            throw ProjectException::accessDenied();
        }
    }

    public function assertProjectIsOpen(Project $project): void
    {
        if ($project->status->isClosed()) {
            throw ProjectException::closed();
        }
    }
}
