<?php

namespace Tests\Feature\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTimeLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = $this->createUser('project-manager');
        $this->developer = $this->createUser('developer');

        $project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $project->members()->create(['user_id' => $this->developer->id, 'role' => 'developer']);

        $this->task = Task::factory()->create([
            'project_id'  => $project->id,
            'created_by'  => $this->pm->id,
            'assigned_to' => $this->developer->id,
        ]);
    }

    public function test_developer_can_log_time_on_assigned_task(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/tasks/{$this->task->id}/time-logs", [
                'minutes'     => 90,
                'description' => 'Trabajo en feature X',
            ])->assertCreated()
            ->assertJsonPath('items.minutes', 90)
            ->assertJsonPath('items.hours', 1.5);
    }

    public function test_non_assigned_user_cannot_log_time(): void
    {
        $other = $this->createUser('developer');

        $this->actingAs($other)
            ->postJson("/api/v1/tasks/{$this->task->id}/time-logs", [
                'minutes' => 60,
            ])->assertForbidden();
    }

    public function test_minutes_must_be_positive(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/tasks/{$this->task->id}/time-logs", [
                'minutes' => 0,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['minutes']);
    }

    public function test_minutes_cannot_exceed_one_day(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/tasks/{$this->task->id}/time-logs", [
                'minutes' => 1441,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['minutes']);
    }

    public function test_can_list_time_logs_for_task(): void
    {
        $this->actingAs($this->developer)
            ->getJson("/api/v1/tasks/{$this->task->id}/time-logs")
            ->assertOk()
            ->assertJsonStructure(['status', 'items', 'message']);
    }
}
