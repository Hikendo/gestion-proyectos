<?php

namespace Tests\Feature\Project;

use App\Models\Deliverable;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverableTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $client;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm     = User::factory()->create();
        $this->client = User::factory()->create();

        $this->pm->assignRole('project-manager');
        $this->client->assignRole('client');

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project->members()->create(['user_id' => $this->client->id, 'role' => 'client']);
    }

    public function test_pm_can_create_deliverable(): void
    {
        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/deliverables", [
                'name'          => 'Módulo de login',
                'delivery_date' => '2025-07-01',
            ])->assertCreated();
    }

    public function test_pm_can_approve_deliverable(): void
    {
        $deliverable = Deliverable::factory()->create([
            'project_id' => $this->project->id,
            'approved'   => false,
        ]);

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/deliverables/{$deliverable->id}/approve")
            ->assertOk();

        $this->assertTrue($deliverable->fresh()->approved);
    }

    public function test_approved_deliverable_cannot_be_approved_again(): void
    {
        $deliverable = Deliverable::factory()->create([
            'project_id' => $this->project->id,
            'approved'   => true,
        ]);

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/deliverables/{$deliverable->id}/approve")
            ->assertUnprocessable();
    }

    public function test_approved_deliverable_cannot_be_updated(): void
    {
        $deliverable = Deliverable::factory()->create([
            'project_id' => $this->project->id,
            'approved'   => true,
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/deliverables/{$deliverable->id}", [
                'name' => 'Intento',
            ])->assertUnprocessable();
    }
}
