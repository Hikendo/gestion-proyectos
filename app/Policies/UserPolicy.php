<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    // Listar usuarios
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    // Ver un usuario
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->hasRole('super-admin');
    }

    // Crear usuario — solo super-admin
    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    // Editar — el propio usuario o super-admin
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id
            || $user->hasRole('super-admin');
    }

    // Eliminar — solo super-admin, no a sí mismo
    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id
            && $user->hasRole('super-admin');
    }
}
