<?php

namespace Tests\Feature\Project;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectScopedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected Project $project1;
    protected Project $project2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = $this->createUser('project-manager');
        $this->developer = $this->createUser('developer');

        $this->project1 = Project::factory()->create(['owner_id' => $this->pm->id]);
        $this->project1->members()->create([
            'user_id' => $this->developer->id,
            'role'    => 'developer',
        ]);

        // Project 2: only PM is member, developer is NOT
        $this->project2 = Project::factory()->create(['owner_id' => $this->pm->id]);
    }

    /**
     * A user who is not a member of a project cannot view its tasks.
     */
    public function test_non_member_cannot_view_project_tasks(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project2->id,
            'created_by' => $this->pm->id,
        ]);

        // Developer is NOT a member of project2
        $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$this->project2->id}/tasks")
            ->assertForbidden();
    }

    /**
     * A member of one project can view tasks in that project.
     */
    public function test_member_can_view_own_project_tasks(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project1->id,
            'created_by' => $this->pm->id,
        ]);

        // Developer IS a member of project1
        $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$this->project1->id}/tasks")
            ->assertOk();
    }

    /**
     * Cross-project access is blocked: a task from project2 cannot be
     * accessed via a URL that specifies project1.
     */
    public function test_task_from_wrong_project_returns_404(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project2->id,
            'created_by' => $this->pm->id,
        ]);

        $this->actingAs($this->pm)
            ->getJson("/api/v1/projects/{$this->project1->id}/tasks/{$task->id}")
            ->assertNotFound();
    }

    /**
     * A developer in project1 cannot update a task in project2
     * (cross-project edit attempt via direct task endpoint).
     */
    public function test_cross_project_permission_scoping(): void
    {
        // Developer is member of project1
        $taskInProject2 = Task::factory()->create([
            'project_id'  => $this->project2->id,
            'created_by'  => $this->pm->id,
            'assigned_to' => $this->pm->id,
        ]);

        // Attempt to edit via project1 URL → not found (belongsToProject check)
        $this->actingAs($this->developer)
            ->putJson("/api/v1/projects/{$this->project1->id}/tasks/{$taskInProject2->id}", [
                'title' => 'Hacked',
            ])
            ->assertNotFound();
    }
}