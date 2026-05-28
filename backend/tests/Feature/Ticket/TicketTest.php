<?php

namespace Tests\Feature\Ticket;

use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
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
            ['user_id' => $this->developer->id, 'role' => 'developer'],
            ['user_id' => $this->client->id,    'role' => 'client'],
        ]);
    }

    public function test_member_can_create_ticket(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets", [
                'subject'  => 'Bug en login',
                'priority' => 'high',
            ])->assertCreated()
            ->assertJsonPath('items.status', TicketStatus::Open->value);
    }

    public function test_client_can_create_ticket(): void
    {
        $this->actingAs($this->client)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets", [
                'subject' => 'Consulta de cliente',
            ])->assertCreated();
    }

    public function test_pm_can_assign_ticket(): void
    {
        $ticket = Ticket::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->client->id,
            'status'     => TicketStatus::Open->value,
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}", [
                'assigned_to' => $this->developer->id,
            ])->assertOk()
            ->assertJsonPath('items.assignee.id', $this->developer->id);
    }

    public function test_closed_ticket_cannot_be_updated(): void
    {
        $ticket = Ticket::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->client->id,
            'status'     => TicketStatus::Closed->value,
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}", [
                'subject' => 'Intento',
            ])->assertUnprocessable();
    }

    public function test_ticket_from_other_project_returns_404(): void
    {
        $other  = Project::factory()->create(['owner_id' => $this->pm->id]);
        $ticket = Ticket::factory()->create([
            'project_id' => $other->id,
            'created_by' => $this->pm->id,
        ]);

        $this->actingAs($this->pm)
            ->getJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}")
            ->assertNotFound();
    }
}
