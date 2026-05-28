<?php

namespace Tests\Feature\Task;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = $this->createUser('project-manager');
        $this->developer = $this->createUser('developer');

        $this->project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project->members()->create([
            'user_id' => $this->developer->id,
            'role'    => 'developer',
        ]);
    }

    public function test_pm_can_create_task(): void
    {
        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'       => 'Tarea de prueba',
                'priority'    => 'high',
                'assigned_to' => $this->developer->id,
            ])->assertCreated();
    }

    public function test_developer_can_move_task_to_in_progress(): void
    {
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => TaskStatus::Pending->value,
        ]);

        $this->actingAs($this->developer)
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'status' => TaskStatus::InProgress->value,
            ])->assertOk()
            ->assertJsonPath('items.status', TaskStatus::InProgress->value);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => TaskStatus::Pending->value,
        ]);

        // Pending → Done es transición inválida
        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'status' => TaskStatus::Done->value,
            ])->assertUnprocessable();
    }

    public function test_done_task_cannot_be_edited(): void
    {
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => TaskStatus::Done->value,
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'title' => 'Cambio',
            ])->assertForbidden();
    }

    public function test_task_from_another_project_returns_404(): void
    {
        $other = Project::factory()->create(['owner_id' => $this->pm->id]);
        $task  = Task::factory()->create([
            'project_id' => $other->id,
            'created_by' => $this->pm->id,
        ]);

        $this->actingAs($this->pm)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}")
            ->assertNotFound();
    }
}
