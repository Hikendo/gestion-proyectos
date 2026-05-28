<?php

namespace App\Policies;

use App\Models\Deliverable;
use App\Models\User;

class DeliverablePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('deliverable.view');
    }

    public function view(User $user, Deliverable $deliverable): bool
    {
        return $user->canForProject($deliverable->project, 'deliverable.view');
    }

    public function create(User $user): bool
    {
        return $user->can('deliverable.create');
    }

    public function update(User $user, Deliverable $deliverable): bool
    {
        if ($deliverable->approved) {
            return false;
        }

        return $user->canForProject($deliverable->project, 'deliverable.edit')
            && ($deliverable->project->owner_id === $user->id
                || $user->hasProjectRole($deliverable->project, 'manager'));
    }

    /**
     * Un entregable aprobado no puede ser aprobado de nuevo.
     */
    public function approve(User $user, Deliverable $deliverable): bool
    {
        if ($deliverable->approved) {
            return false;
        }

        return $user->canForProject($deliverable->project, 'deliverable.approve')
            && ($deliverable->project->owner_id === $user->id
                || $user->hasProjectRole($deliverable->project, 'manager'));
    }
}
