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

    public function create(User $user): bool
    {
        return $user->can('objective.create');
    }

    public function update(User $user, Objective $objective): bool
    {
        return $user->can('objective.edit')
            && $user->hasRole('project-manager');
    }
}
