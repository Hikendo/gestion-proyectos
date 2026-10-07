<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPhaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Miembros SIN rol global de Spatie (solo rol de membresía de proyecto):
        // reproduce el escenario que provocaba el 403 en producción.
        $this->pm        = User::factory()->create();
        $this->developer = User::factory()->create();

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project->members()->createMany([
            ['user_id' => $this->pm->id,        'role' => 'manager'],
            ['user_id' => $this->developer->id, 'role' => 'developer'],
        ]);
    }

    /**
     * Regresión: un Manager de proyecto SIN rol global debe poder crear fases.
     * Antes daba 403 porque StoreProjectPhaseRequest usaba can('phase.create') GLOBAL.
     */
    public function test_manager_member_without_global_role_can_create_phase(): void
    {
        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/phases", [
                'name' => 'Fase 1',
            ])->assertCreated()
            ->assertJsonPath('items.name', 'Fase 1');
    }

    /**
     * El rol de membresía developer NO incluye phase.create → debe denegarse.
     */
    public function test_developer_member_cannot_create_phase(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/projects/{$this->project->id}/phases", [
                'name' => 'Fase prohibida',
            ])->assertForbidden();
    }
}
