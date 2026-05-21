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
        return $user->can('blocker.create');
    }

    public function update(User $user, Blocker $blocker): bool
    {
        if ($blocker->resolved) {
            return false;
        }

        return $user->can('blocker.edit');
    }

    public function resolve(User $user, Blocker $blocker): bool
    {
        if ($blocker->resolved) {
            return false;
        }

        return $user->can('blocker.resolve');
    }
}
