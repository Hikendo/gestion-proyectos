<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser('developer', ['password' => bcrypt('password')]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_authenticated_user_can_get_own_profile(): void
    {
        $this->actingAsUser('developer')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'email', 'roles']);
    }

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAsUser('developer')
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }

    public function test_super_admin_can_register_users(): void
    {
        $this->actingAsUser('super-admin')
            ->postJson('/api/v1/auth/register', [
                'name'                  => 'Nuevo Usuario',
                'email'                 => 'nuevo@test.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'developer',
            ])->assertCreated();
    }

    public function test_developer_cannot_register_users(): void
    {
        $this->actingAsUser('developer')
            ->postJson('/api/v1/auth/register', [
                'name'                  => 'Nuevo',
                'email'                 => 'nuevo@test.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'developer',
            ])->assertForbidden();
    }
}
