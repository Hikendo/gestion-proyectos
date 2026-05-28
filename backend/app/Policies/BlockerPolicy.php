<?php

namespace App\Policies;

use App\Models\Blocker;
use App\Models\User;

class BlockerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('blocker.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Blocker $blocker): bool
    {
        if ($blocker->resolved) {
            return false;
        }

        return $user->canForProject($blocker->project, 'blocker.edit')
            && ($blocker->project->owner_id === $user->id
                || $user->hasProjectRole($blocker->project, 'manager'));
    }

    public function resolve(User $user, Blocker $blocker): bool
    {
        if ($blocker->resolved) {
            return false;
        }

        return $user->canForProject($blocker->project, 'blocker.resolve')
            && ($blocker->project->owner_id === $user->id
                || $user->hasProjectRole($blocker->project, 'manager'));
    }
}
