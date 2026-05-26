<?php

namespace Tests\Feature\Project;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm = User::factory()->create();
        $this->pm->assignRole('project-manager');
        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
    }

    public function test_pm_can_create_milestone(): void
    {
        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/milestones", [
                'title'       => 'MVP',
                'target_date' => '2025-06-01',
            ])->assertCreated()
            ->assertJsonPath('items.title', 'MVP');
    }

    public function test_pm_can_complete_milestone(): void
    {
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
            'completed'  => false,
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/milestones/{$milestone->id}", [
                'completed' => true,
            ])->assertOk()
            ->assertJsonPath('items.completed', true);
    }

    public function test_completed_milestone_cannot_be_deleted(): void
    {
        $milestone = Milestone::factory()->create([
            'project_id' => $this->project->id,
            'completed'  => true,
        ]);

        $this->actingAs($this->pm)
            ->deleteJson("/api/v1/projects/{$this->project->id}/milestones/{$milestone->id}")
            ->assertForbidden();
    }
}
