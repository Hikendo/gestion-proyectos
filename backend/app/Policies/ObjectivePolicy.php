<?php

namespace App\Policies;

use App\Models\Objective;
use App\Models\User;

class ObjectivePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('objective.view');
    }

    public function view(User $user, Objective $objective): bool
    {
        return $user->canForProject($objective->project, 'objective.view');
    }

    public function create(User $user): bool
    {
        return $user->can('objective.create');
    }

    public function update(User $user, Objective $objective): bool
    {
        return $user->canForProject($objective->project, 'objective.edit')
            && ($objective->project->owner_id === $user->id
                || $user->hasProjectRole($objective->project, 'manager'));
    }
}
