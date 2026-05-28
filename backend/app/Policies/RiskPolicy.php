<?php

namespace App\Policies;

use App\Models\Risk;
use App\Models\User;

class RiskPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('risk.view');
    }

    public function create(User $user): bool
    {
        return $user->can('risk.create');
    }

    public function update(User $user, Risk $risk): bool
    {
        return $user->canForProject($risk->project, 'risk.edit')
            && ($risk->project->owner_id === $user->id
                || $user->hasProjectRole($risk->project, 'manager'));
    }

    public function delete(User $user, Risk $risk): bool
    {
        return $user->canForProject($risk->project, 'risk.delete')
            && ($risk->project->owner_id === $user->id
                || $user->hasProjectRole($risk->project, 'manager'));
    }
}
