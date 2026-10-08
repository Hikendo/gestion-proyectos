<?php

namespace Tests\Feature\Project;

use App\Models\Blocker;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    /**
     * La ruta sin prefijo de proyecto /blockers/{id}/attachments (la que usa el
     * frontend) debe funcionar; antes devolvía 404.
     */
    public function test_pm_can_upload_blocker_attachment_via_unprefixed_route(): void
    {
        Storage::fake('local');

        $blocker = Blocker::factory()->create(['project_id' => $this->project->id]);
        $file = UploadedFile::fake()->create('evidence.pdf', 50, 'application/pdf');

        $this->actingAs($this->pm)
            ->postJson("/api/v1/blockers/{$blocker->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('attachments', [
            'attachable_id'   => $blocker->id,
            'attachable_type' => Blocker::class,
        ]);
    }

    /**
     * Un usuario ajeno al proyecto no puede subir adjuntos a un blocker.
     */
    public function test_non_member_cannot_upload_blocker_attachment(): void
    {
        Storage::fake('local');

        $blocker = Blocker::factory()->create(['project_id' => $this->project->id]);
        $outsider = User::factory()->create();
        $file = UploadedFile::fake()->create('evidence.pdf', 50, 'application/pdf');

        $this->actingAs($outsider)
            ->postJson("/api/v1/blockers/{$blocker->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertForbidden();
    }
}
