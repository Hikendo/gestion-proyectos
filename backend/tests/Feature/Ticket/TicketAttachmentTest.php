<?php

namespace Tests\Feature\Ticket;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Adjuntos de ticket: el cliente (creador) puede agregar evidencia mientras el
 * ticket NO esté cerrado, aunque ya esté En Progreso o Resuelto.
 */
class TicketAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $client;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm     = User::factory()->create();
        $this->client = User::factory()->create();

        $this->pm->assignRole('project-manager');
        $this->client->assignRole('client');

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project->members()->createMany([
            ['user_id' => $this->pm->id,     'role' => 'manager'],
            ['user_id' => $this->client->id, 'role' => 'client'],
        ]);
    }

    private function makeTicket(string $status, int $createdBy): Ticket
    {
        return Ticket::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $createdBy,
            'status'     => $status,
        ]);
    }

    public function test_client_can_upload_attachment_to_in_progress_ticket(): void
    {
        $ticket = $this->makeTicket('in_progress', $this->client->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->client)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('attachments', [
            'attachable_id'   => $ticket->id,
            'attachable_type' => Ticket::class,
        ]);
    }

    public function test_client_can_upload_attachment_to_resolved_ticket(): void
    {
        $ticket = $this->makeTicket('resolved', $this->client->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->client)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertCreated();
    }

    public function test_client_cannot_upload_attachment_to_closed_ticket(): void
    {
        $ticket = $this->makeTicket('closed', $this->client->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->client)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_client_cannot_upload_attachment_to_another_users_ticket(): void
    {
        // Ticket creado por el PM: el cliente no es el creador.
        $ticket = $this->makeTicket('in_progress', $this->pm->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->client)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertForbidden();
    }

    public function test_pm_can_upload_attachment_to_ticket(): void
    {
        $ticket = $this->makeTicket('in_progress', $this->client->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->pm)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertCreated();
    }

    /**
     * El PM conserva la capacidad previa de subir adjuntos aunque el ticket
     * esté cerrado (la nueva restricción de estado solo aplica al cliente).
     */
    public function test_pm_can_upload_attachment_to_closed_ticket(): void
    {
        $ticket = $this->makeTicket('closed', $this->client->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->pm)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertCreated();
    }

    public function test_upload_requires_at_least_one_attachment(): void
    {
        $ticket = $this->makeTicket('open', $this->client->id);

        $this->actingAs($this->client)
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [])
            ->assertUnprocessable();
    }

    /**
     * La ruta heredada con prefijo de proyecto sigue funcionando.
     */
    public function test_legacy_project_scoped_route_still_works(): void
    {
        $ticket = $this->makeTicket('in_progress', $this->client->id);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->client)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertCreated();
    }

    /**
     * La ruta heredada valida que el ticket pertenezca al proyecto de la URL.
     */
    public function test_legacy_project_scoped_route_rejects_ticket_from_other_project(): void
    {
        $other = Project::factory()->create(['owner_id' => $this->pm->id]);
        $other->members()->create(['user_id' => $this->pm->id, 'role' => 'manager']);

        $ticket = Ticket::factory()->create([
            'project_id' => $other->id,
            'created_by' => $this->client->id,
            'status'     => 'in_progress',
        ]);

        $file = UploadedFile::fake()->create('evidencia.pdf', 100, 'application/pdf');

        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/tickets/{$ticket->id}/attachments", [
                'attachments' => [$file],
            ])
            ->assertNotFound();
    }
}
