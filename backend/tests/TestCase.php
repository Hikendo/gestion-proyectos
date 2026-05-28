<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crea un usuario con el rol indicado y retorna tipo User correcto.
     * Elimina el P1006 de Intelephense en todos los tests.
     */
    protected function createUser(string $role, array $attributes = []): User
    {
        /** @var User $user */
        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Crea un usuario y autentica directamente.
     */
    protected function actingAsUser(string $role, array $attributes = []): static
    {
        $user = $this->createUser($role, $attributes);

        return $this->actingAs($user);
    }
}
