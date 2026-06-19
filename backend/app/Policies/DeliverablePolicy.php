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

        // Owner siempre puede editar entregables de su proyecto
        if ($deliverable->project->owner_id === $user->id) {
            return true;
        }

        return $user->canForProject($deliverable->project, 'deliverable.edit')
            && $user->hasProjectRole($deliverable->project, 'manager');
    }

    /**
     * Un entregable aprobado no puede ser aprobado de nuevo.
     * Un entregable con dependencia (parent_id) no puede aprobarse
     * hasta que su padre esté aprobado.
     */
    public function approve(User $user, Deliverable $deliverable): bool
    {
        if ($deliverable->approved) {
            return false;
        }

        // Owner siempre puede aprobar entregables de su proyecto
        if ($deliverable->project->owner_id === $user->id) {
            return true;
        }

        // Validar dependencia: no aprobar si el padre no está aprobado
        if ($deliverable->parent_id) {
            $parent = Deliverable::find($deliverable->parent_id);
            if ($parent && ! $parent->approved) {
                return false;
            }
        }

        return $user->canForProject($deliverable->project, 'deliverable.approve')
            && $user->hasProjectRole($deliverable->project, 'manager');
    }
}
