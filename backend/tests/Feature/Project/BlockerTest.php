<?php

namespace Tests\Feature\Project;

use App\Models\Blocker;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockerTest extends TestCase
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
        $this->project->members()->create(['user_id' => $this->developer->id, 'role' => 'developer']);
    }

    public function test_member_can_create_blocker(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/projects/{$this->project->id}/blockers", [
                'title'    => 'Sin acceso a BD',
                'severity' => 'high',
            ])->assertCreated();
    }

    public function test_pm_can_resolve_blocker(): void
    {
        $blocker = Blocker::factory()->create([
            'project_id' => $this->project->id,
            'resolved'   => false,
        ]);

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/blockers/{$blocker->id}/resolve")
            ->assertOk();

        $this->assertTrue($blocker->fresh()->resolved);
    }

    public function test_already_resolved_blocker_cannot_be_resolved_again(): void
    {
        $blocker = Blocker::factory()->create([
            'project_id' => $this->project->id,
            'resolved'   => true,
        ]);

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/blockers/{$blocker->id}/resolve")
            ->assertUnprocessable();
    }

    public function test_index_excludes_resolved_by_default(): void
    {
        Blocker::factory()->create(['project_id' => $this->project->id, 'resolved' => false]);
        Blocker::factory()->create(['project_id' => $this->project->id, 'resolved' => true]);

        $response = $this->actingAs($this->pm)
            ->getJson("/api/v1/projects/{$this->project->id}/blockers")
            ->assertOk();

        $this->assertCount(1, $response->json('items'));
    }

    public function test_index_includes_resolved_when_requested(): void
    {
        Blocker::factory()->create(['project_id' => $this->project->id, 'resolved' => false]);
        Blocker::factory()->create(['project_id' => $this->project->id, 'resolved' => true]);

        $response = $this->actingAs($this->pm)
            ->getJson("/api/v1/projects/{$this->project->id}/blockers?include_resolved=true")
            ->assertOk();

        $this->assertCount(2, $response->json('items'));
    }
}
