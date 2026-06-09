<?php

namespace Tests\Feature\Auth;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = $this->createUser('project-manager');
        $this->developer = $this->createUser('developer');

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project->members()->create([
            'user_id' => $this->developer->id,
            'role'    => 'developer',
        ]);
    }

    /**
     * The /api/auth/me endpoint returns the user's permissions.
     */
    public function test_me_endpoint_returns_permissions(): void
    {
        $response = $this->actingAs($this->developer)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $item = $response->json('items');
        $this->assertIsArray($item['permissions']);
        $this->assertContains('task.edit-own', $item['permissions']);
    }

    /**
     * The refresh-permissions endpoint returns fresh permissions.
     */
    public function test_refresh_permissions_returns_updated_permissions(): void
    {
        $response = $this->actingAs($this->developer)
            ->postJson('/api/v1/auth/refresh-permissions')
            ->assertOk();

        $this->assertTrue($response->json('status'));
        $this->assertIsArray($response->json('items'));
        $this->assertContains('task.edit-own', $response->json('items'));
    }

    /**
     * Unauthenticated users cannot access refresh-permissions.
     */
    public function test_unauthenticated_cannot_refresh_permissions(): void
    {
        $this->postJson('/api/v1/auth/refresh-permissions')
            ->assertUnauthorized();
    }

    /**
     * The task show endpoint includes field_permissions for the requesting user.
     */
    public function test_task_show_includes_field_permissions(): void
    {
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
        ]);

        $response = $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}")
            ->assertOk();

        $item = $response->json('items');
        $this->assertArrayHasKey('field_permissions', $item);
        $this->assertIsArray($item['field_permissions']);
        $this->assertArrayHasKey('title', $item['field_permissions']);
        $this->assertArrayHasKey('status', $item['field_permissions']);
        $this->assertArrayHasKey('assigned_to', $item['field_permissions']);
    }
}