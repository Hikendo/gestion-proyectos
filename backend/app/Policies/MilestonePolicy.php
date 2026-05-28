<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('milestone.view');
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return $user->canForProject($milestone->project, 'milestone.view');
    }

    public function create(User $user): bool
    {
        return $user->can('milestone.create');
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $user->canForProject($milestone->project, 'milestone.edit')
            && ($milestone->project->owner_id === $user->id
                || $user->hasProjectRole($milestone->project, 'manager'));
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        if ($milestone->completed) {
            return false;
        }

        return $user->canForProject($milestone->project, 'milestone.delete')
            && ($milestone->project->owner_id === $user->id
                || $user->hasProjectRole($milestone->project, 'manager'));
    }
}
