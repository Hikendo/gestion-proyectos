<?php

namespace Tests\Feature\Ticket;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected User $client;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = User::factory()->create();
        $this->developer = User::factory()->create();
        $this->client    = User::factory()->create();

        $this->pm->assignRole('project-manager');
        $this->developer->assignRole('developer');
        $this->client->assignRole('client');

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project->members()->createMany([
            ['user_id' => $this->pm->id,        'role' => 'manager'],
            ['user_id' => $this->developer->id, 'role' => 'developer'],
            ['user_id' => $this->client->id,    'role' => 'client'],
        ]);
    }

    protected function makeTicket(?User $creator = null): Ticket
    {
        return Ticket::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => ($creator ?? $this->client)->id,
        ]);
    }

    public function test_member_can_add_comment(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->developer)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/comments", [
                'comment' => 'Estoy revisando el ticket.',
            ])->assertCreated()
            ->assertJsonPath('items.comment', 'Estoy revisando el ticket.');
    }

    public function test_client_can_add_comment_on_own_ticket(): void
    {
        $ticket = $this->makeTicket($this->client);

        $this->actingAs($this->client)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/comments", [
                'comment' => '¿Alguna novedad con mi ticket?',
            ])->assertCreated();
    }

    public function test_client_cannot_comment_on_others_ticket(): void
    {
        $ticket = $this->makeTicket($this->pm);

        $this->actingAs($this->client)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/comments", [
                'comment' => 'No debería poder comentar aquí.',
            ])->assertForbidden();
    }

    public function test_index_lists_comments(): void
    {
        $ticket = $this->makeTicket();
        $ticket->comments()->create(['user_id' => $this->developer->id, 'comment' => 'Primer comentario']);

        $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/comments")
            ->assertOk()
            ->assertJsonPath('items.0.comment', 'Primer comentario')
            ->assertJsonPath('items.0.user.id', $this->developer->id);
    }

    public function test_author_can_delete_own_comment(): void
    {
        $ticket  = $this->makeTicket();
        $comment = $ticket->comments()->create(['user_id' => $this->developer->id, 'comment' => 'Comentario']);

        $this->actingAs($this->developer)
            ->deleteJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/comments/{$comment->id}")
            ->assertOk();

        $this->assertDatabaseMissing('ticket_comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $ticket  = $this->makeTicket();
        $comment = $ticket->comments()->create(['user_id' => $this->pm->id, 'comment' => 'Comentario del PM']);

        $this->actingAs($this->developer)
            ->deleteJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/comments/{$comment->id}")
            ->assertForbidden();
    }
}
