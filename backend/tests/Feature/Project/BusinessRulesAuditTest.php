<?php

namespace Tests\Feature\Project;

use App\Enums\PhaseStatus;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessRulesAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $pm;
    protected User $developer;
    protected User $qa;
    protected User $client;
    protected Project $project;
    protected ProjectPhase $phase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->pm        = User::factory()->create();
        $this->developer = User::factory()->create();
        $this->qa        = User::factory()->create();
        $this->client    = User::factory()->create();

        $this->pm->assignRole('project-manager');
        $this->developer->assignRole('developer');
        $this->qa->assignRole('qa');
        $this->client->assignRole('client');

        $this->project = Project::factory()->create([
            'owner_id' => $this->pm->id,
            'status'   => 'active',
        ]);

        $this->project->members()->createMany([
            ['user_id' => $this->pm->id,        'role' => 'manager'],
            ['user_id' => $this->developer->id, 'role' => 'developer'],
            ['user_id' => $this->qa->id,        'role' => 'qa'],
            ['user_id' => $this->client->id,    'role' => 'client'],
        ]);

        $this->phase = ProjectPhase::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now()->subDays(7),
            'end_date'   => now()->addDays(30),
            'status'     => PhaseStatus::InProgress,
        ]);
    }

    public function test_cannot_create_task_in_expired_phase(): void
    {
        $p = ProjectPhase::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now()->subDays(30),
            'end_date'   => now()->subDays(1),
            'status'     => PhaseStatus::InProgress,
        ]);

        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'    => 'Test',
                'phase_id' => $p->id,
            ])->assertStatus(422);
    }

    public function test_cannot_create_task_in_completed_phase(): void
    {
        $p = ProjectPhase::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now()->subDays(30),
            'end_date'   => now()->subDays(1),
            'status'     => PhaseStatus::Completed,
        ]);

        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'    => 'Test',
                'phase_id' => $p->id,
            ])->assertStatus(422);
    }

    public function test_can_create_task_in_maintenance_phase(): void
    {
        $p = ProjectPhase::factory()->create([
            'project_id' => $this->project->id,
            'start_date' => now()->subDays(7),
            'end_date'   => null,
            'status'     => PhaseStatus::InProgress,
        ]);

        $this->actingAs($this->pm)
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'    => 'Mant',
                'phase_id' => $p->id,
            ])->assertCreated();
    }

    public function test_cannot_delete_phase_with_tasks(): void
    {
        Task::factory()->create([
            'project_id'  => $this->project->id,
            'phase_id'    => $this->phase->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => 'pending',
        ]);

        $this->actingAs($this->pm)
            ->deleteJson("/api/v1/projects/{$this->project->id}/phases/{$this->phase->id}")
            ->assertStatus(422);
    }

    public function test_can_delete_empty_phase(): void
    {
        $empty = ProjectPhase::factory()->create(['project_id' => $this->project->id]);

        $this->actingAs($this->pm)
            ->deleteJson("/api/v1/projects/{$this->project->id}/phases/{$empty->id}")
            ->assertOk();
    }

    public function test_cannot_create_circular_dependency(): void
    {
        $a = Deliverable::factory()->create([
            'project_id'    => $this->project->id,
            'delivery_date' => now()->addDays(30),
        ]);
        $b = Deliverable::factory()->create([
            'project_id'    => $this->project->id,
            'delivery_date' => now()->addDays(30),
            'parent_id'     => $a->id,
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/deliverables/{$a->id}", [
                'name'      => 'Cycle',
                'parent_id' => $b->id,
            ])->assertStatus(422);
    }

    public function test_child_not_approved_before_parent(): void
    {
        $parent = Deliverable::factory()->create([
            'project_id'    => $this->project->id,
            'delivery_date' => now()->addDays(30),
        ]);
        $child = Deliverable::factory()->create([
            'project_id'    => $this->project->id,
            'delivery_date' => now()->addDays(30),
            'parent_id'     => $parent->id,
        ]);

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/deliverables/{$child->id}/approve")
            ->assertStatus(422);

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/deliverables/{$parent->id}/approve")
            ->assertOk();

        $this->actingAs($this->pm)
            ->patchJson("/api/v1/projects/{$this->project->id}/deliverables/{$child->id}/approve")
            ->assertOk();
    }

    public function test_cannot_log_time_on_done_task(): void
    {
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'phase_id'    => $this->phase->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => 'done',
        ]);

        $this->actingAs($this->developer)
            ->postJson("/api/v1/tasks/{$task->id}/time-logs", [
                'minutes'     => 60,
                'description' => 'Nope',
            ])->assertStatus(422);
    }

    public function test_developer_sees_only_own_tasks(): void
    {
        $other = User::factory()->create();
        $other->assignRole('developer');
        $this->project->members()->create(['user_id' => $other->id, 'role' => 'developer']);

        $mine = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => 'pending',
        ]);
        $theirs = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $other->id,
            'created_by'  => $this->pm->id,
            'status'      => 'pending',
        ]);

        $res = $this->actingAs($this->developer)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks")
            ->assertOk();

        $ids = collect($res->json('items.data'))->pluck('id');
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_pm_sees_all_tasks(): void
    {
        $other = User::factory()->create();
        $other->assignRole('developer');
        $this->project->members()->create(['user_id' => $other->id, 'role' => 'developer']);

        $t1 = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => 'pending',
        ]);
        $t2 = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $other->id,
            'created_by'  => $this->pm->id,
            'status'      => 'pending',
        ]);

        $res = $this->actingAs($this->pm)
            ->getJson("/api/v1/projects/{$this->project->id}/tasks")
            ->assertOk();

        $ids = collect($res->json('items.data'))->pluck('id');
        $this->assertContains($t1->id, $ids);
        $this->assertContains($t2->id, $ids);
    }

    public function test_client_sees_only_own_tickets(): void
    {
        $other = User::factory()->create();
        $other->assignRole('client');
        $this->project->members()->create(['user_id' => $other->id, 'role' => 'client']);

        $mine = Ticket::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->client->id,
            'status'     => 'open',
        ]);
        $theirs = Ticket::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $other->id,
            'status'     => 'open',
        ]);

        $res = $this->actingAs($this->client)
            ->getJson("/api/v1/projects/{$this->project->id}/tickets")
            ->assertOk();

        $ids = collect($res->json('items.data'))->pluck('id');
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_done_task_cannot_be_edited(): void
    {
        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'phase_id'    => $this->phase->id,
            'assigned_to' => $this->developer->id,
            'created_by'  => $this->pm->id,
            'status'      => 'done',
            'title'       => 'Original',
        ]);

        $this->actingAs($this->pm)
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'title' => 'Changed',
            ])->assertStatus(403);
    }

    public function test_task_progress_calculated(): void
    {
        $task = Task::factory()->create([
            'project_id'      => $this->project->id,
            'phase_id'        => $this->phase->id,
            'assigned_to'     => $this->developer->id,
            'created_by'      => $this->pm->id,
            'status'          => 'in_progress',
            'estimated_hours' => 10,
            'worked_hours'    => 7,
        ]);

        $this->assertEquals(70, $task->fresh()->progress);
    }

    public function test_done_task_progress_100(): void
    {
        $task = Task::factory()->create([
            'project_id'      => $this->project->id,
            'phase_id'        => $this->phase->id,
            'assigned_to'     => $this->developer->id,
            'created_by'      => $this->pm->id,
            'status'          => 'done',
            'estimated_hours' => 10,
            'worked_hours'    => 3,
        ]);

        $this->assertEquals(100, $task->fresh()->progress);
    }

    public function test_project_uses_weighted_average(): void
    {
        $small = ProjectPhase::factory()->create([
            'project_id' => $this->project->id,
            'progress'   => 100,
        ]);
        Task::factory()->create([
            'project_id'      => $this->project->id,
            'phase_id'        => $small->id,
            'assigned_to'     => $this->developer->id,
            'created_by'      => $this->pm->id,
            'status'          => 'done',
            'estimated_hours' => 5,
        ]);

        $large = ProjectPhase::factory()->create([
            'project_id' => $this->project->id,
            'progress'   => 10,
        ]);
        Task::factory()->create([
            'project_id'      => $this->project->id,
            'phase_id'        => $large->id,
            'assigned_to'     => $this->developer->id,
            'created_by'      => $this->pm->id,
            'status'          => 'in_progress',
            'estimated_hours' => 95,
        ]);

        $progress = $this->project->fresh()->progress;
        $this->assertLessThan(30, $progress, "Weighted avg ~15%, got {$progress}");
    }
}