<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMemberTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = User::factory()->create();
        $this->developer = User::factory()->create();

        $this->pm->assignRole('project-manager');
        $this->developer->assignRole('developer');

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
    }

    public function test_pm_can_add_member(): void
    {
        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/members", [
                'user_id' => $this->developer->id,
                'role'    => 'developer',
            ])->assertCreated()
            ->assertJsonPath('data.role', 'developer');
    }

    public function test_cannot_add_duplicate_member(): void
    {
        $this->project->members()->create([
            'user_id' => $this->developer->id,
            'role'    => 'developer',
        ]);

        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/members", [
                'user_id' => $this->developer->id,
                'role'    => 'developer',
            ])->assertConflict();
    }

    public function test_pm_can_remove_member(): void
    {
        $this->project->members()->create([
            'user_id' => $this->developer->id,
            'role'    => 'developer',
        ]);

        $this->actingAs($this->pm)
            ->deleteJson("/api/v1/projects/{$this->project->id}/members/{$this->developer->id}")
            ->assertOk();

        $this->assertDatabaseMissing('project_members', [
            'project_id' => $this->project->id,
            'user_id'    => $this->developer->id,
        ]);
    }

    public function test_cannot_remove_owner(): void
    {
        $this->actingAs($this->pm)
            ->deleteJson("/api/v1/projects/{$this->project->id}/members/{$this->pm->id}")
            ->assertUnprocessable();
    }
}
