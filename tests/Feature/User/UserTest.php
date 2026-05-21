<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    // ── INDEX ────────────────────────────────────────────────────────

    public function test_admin_can_list_users(): void
    {
        $admin = $this->createUser('super-admin');
        User::factory(5)->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_developer_cannot_list_users(): void
    {
        $this->actingAsUser('developer')
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    // ── STORE ────────────────────────────────────────────────────────

    public function test_admin_can_create_user(): void
    {
        $this->actingAsUser('super-admin')
            ->postJson('/api/v1/users', [
                'name'                  => 'Juan Dev',
                'email'                 => 'juan@test.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'developer',
            ])->assertCreated()
            ->assertJsonPath('data.email', 'juan@test.com');
    }

    public function test_create_user_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);

        $this->actingAsUser('super-admin')
            ->postJson('/api/v1/users', [
                'name'                  => 'Otro',
                'email'                 => 'taken@test.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
                'role'                  => 'developer',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // ── SHOW ─────────────────────────────────────────────────────────

    public function test_user_can_view_own_profile(): void
    {
        $user = $this->createUser('developer');

        $this->actingAs($user)
            ->getJson("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_user_cannot_view_other_profiles(): void
    {
        $user  = $this->createUser('developer');
        $other = $this->createUser('developer');

        $this->actingAs($user)
            ->getJson("/api/v1/users/{$other->id}")
            ->assertForbidden();
    }

    // ── UPDATE ───────────────────────────────────────────────────────

    public function test_user_can_update_own_name(): void
    {
        $user = $this->createUser('developer');

        $this->actingAs($user)
            ->putJson("/api/v1/users/{$user->id}", ['name' => 'Nuevo Nombre'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo Nombre');
    }

    public function test_only_admin_can_change_roles(): void
    {
        $admin = $this->createUser('super-admin');
        $user  = $this->createUser('developer');

        $this->actingAs($admin)
            ->putJson("/api/v1/users/{$user->id}", ['role' => 'qa'])
            ->assertOk();

        $this->assertTrue($user->fresh()->hasRole('qa'));
    }

    // ── DESTROY ──────────────────────────────────────────────────────

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->createUser('super-admin');
        $user  = $this->createUser('developer');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/users/{$user->id}")
            ->assertOk();

        $this->assertNull(User::find($user->id));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->createUser('super-admin');

        $this->actingAs($admin)
            ->deleteJson("/api/v1/users/{$admin->id}")
            ->assertStatus(422);
    }

    // ── METRICS ──────────────────────────────────────────────────────

    public function test_user_can_view_own_metrics(): void
    {
        $user = $this->createUser('developer');

        $this->actingAs($user)
            ->getJson("/api/v1/users/{$user->id}/metrics")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'assigned_tasks',
                'completed_tasks',
                'worked_hours',
                'performance_score',
            ]]);
    }
}
