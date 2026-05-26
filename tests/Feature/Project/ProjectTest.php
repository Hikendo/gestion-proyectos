<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = $this->createUser('project-manager');
        $this->developer = $this->createUser('developer');
    }

    public function test_user_sees_only_own_projects(): void
    {
        $myProject    = Project::factory()->create(['owner_id' => $this->pm->id]);
        $otherProject = Project::factory()->create();

        $response = $this->actingAs($this->pm)
            ->getJson('/api/v1/projects')
            ->assertOk();

        $ids = collect($response->json('items.data'))->pluck('id');
        $this->assertTrue($ids->contains($myProject->id));
        $this->assertFalse($ids->contains($otherProject->id));
    }

    public function test_pm_can_create_project(): void
    {
        $this->actingAs($this->pm)
            ->postJson('/api/v1/projects', [
                'name' => 'Proyecto Test',
                'code' => 'PT-001',
            ])->assertCreated()
            ->assertJsonPath('items.code', 'PT-001');
    }

    public function test_developer_cannot_create_project(): void
    {
        $this->actingAs($this->developer)
            ->postJson('/api/v1/projects', [
                'name' => 'Proyecto',
                'code' => 'P-001',
            ])->assertForbidden();
    }

    public function test_owner_can_update_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->pm->id]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$project->id}", ['name' => 'Actualizado'])
            ->assertOk()
            ->assertJsonPath('items.name', 'Actualizado');
    }

    public function test_owner_can_delete_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->pm->id]);

        $this->actingAs($this->pm)
            ->deleteJson("/api/v1/projects/{$project->id}")
            ->assertOk();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_non_member_cannot_view_project(): void
    {
        $project = Project::factory()->create(['owner_id' => $this->pm->id]);

        $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$project->id}")
            ->assertForbidden();
    }
}
