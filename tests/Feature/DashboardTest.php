<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    public function test_dashboard_returns_expected_structure(): void
    {
        $user = $this->createUser('project-manager');

        Project::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'summary' => ['total_projects', 'my_pending_tasks', 'open_tickets'],
                'projects',
                'my_tasks',
                'open_tickets',
            ]);
    }

    public function test_dashboard_only_shows_own_data(): void
    {
        $user  = $this->createUser('project-manager');
        $other = $this->createUser('project-manager');

        Project::factory()->create(['owner_id' => $user->id]);
        Project::factory()->create(['owner_id' => $other->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertOk();

        $this->assertEquals(1, $response->json('summary.total_projects'));
    }

    public function test_dashboard_summary_counts_pending_tasks(): void
    {
        $user    = $this->createUser('project-manager');
        $project = Project::factory()->create(['owner_id' => $user->id]);

        \App\Models\Task::factory(3)->create([
            'project_id'  => $project->id,
            'assigned_to' => $user->id,
            'created_by'  => $user->id,
            'status'      => \App\Enums\TaskStatus::Pending->value,
        ]);

        // Tarea completada no debe contar
        \App\Models\Task::factory()->create([
            'project_id'  => $project->id,
            'assigned_to' => $user->id,
            'created_by'  => $user->id,
            'status'      => \App\Enums\TaskStatus::Done->value,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertOk();

        $this->assertEquals(3, $response->json('summary.my_pending_tasks'));
    }

    public function test_member_project_appears_in_dashboard(): void
    {
        $pm      = $this->createUser('project-manager');
        $dev     = $this->createUser('developer');
        $project = Project::factory()->create(['owner_id' => $pm->id]);

        $project->members()->create(['user_id' => $dev->id, 'role' => 'developer']);

        $response = $this->actingAs($dev)
            ->getJson('/api/v1/dashboard')
            ->assertOk();

        $ids = collect($response->json('projects'))->pluck('id');
        $this->assertTrue($ids->contains($project->id));
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized();
    }
}
