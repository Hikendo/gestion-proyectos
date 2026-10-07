<?php

namespace Tests\Feature\Chat;

use App\Events\MessageSent;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GroupChatTest extends TestCase
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

    public function test_index_returns_standard_pagination_shape(): void
    {
        ProjectMessage::create([
            'project_id' => $this->project->id,
            'user_id'    => $this->developer->id,
            'content'    => 'Hola equipo',
        ]);

        $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$this->project->id}/chat/messages")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'project_id', 'user_id', 'user_name', 'content', 'created_at'],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.user_name', $this->developer->name);
    }

    public function test_store_returns_created_message_with_user_name(): void
    {
        Event::fake([MessageSent::class]);

        $this->actingAs($this->developer)
            ->postJson("/api/v1/projects/{$this->project->id}/chat/messages", [
                'content' => 'Mensaje de prueba',
            ])->assertCreated()
            ->assertJsonPath('data.content', 'Mensaje de prueba')
            ->assertJsonPath('data.user_name', $this->developer->name);

        $this->assertDatabaseHas('project_messages', [
            'project_id' => $this->project->id,
            'user_id'    => $this->developer->id,
            'content'    => 'Mensaje de prueba',
        ]);
    }

    public function test_non_member_cannot_read_group_messages(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/v1/projects/{$this->project->id}/chat/messages")
            ->assertForbidden();
    }
}
