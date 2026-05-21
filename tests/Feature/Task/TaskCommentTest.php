<?php

namespace Tests\Feature\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskCommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = User::factory()->create();
        $this->developer = User::factory()->create();

        $this->pm->assignRole('project-manager');
        $this->developer->assignRole('developer');

        $project = Project::factory()->create(['owner_id' => $this->pm->id]);
        $project->members()->create(['user_id' => $this->developer->id, 'role' => 'developer']);

        $this->task = Task::factory()->create([
            'project_id'  => $project->id,
            'created_by'  => $this->pm->id,
            'assigned_to' => $this->developer->id,
        ]);
    }

    public function test_member_can_add_comment(): void
    {
        $this->actingAs($this->developer)
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'comment' => 'Esto es un comentario.',
            ])->assertCreated()
            ->assertJsonPath('data.comment', 'Esto es un comentario.');
    }

    public function test_author_can_delete_own_comment(): void
    {
        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->developer->id,
        ]);

        $this->actingAs($this->developer)
            ->deleteJson("/api/v1/tasks/{$this->task->id}/comments/{$comment->id}")
            ->assertOk();
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $comment = TaskComment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->pm->id,
        ]);

        $this->actingAs($this->developer)
            ->deleteJson("/api/v1/tasks/{$this->task->id}/comments/{$comment->id}")
            ->assertForbidden();
    }
}
