<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\NotificationRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Services\Notifications\NotificationRecipientResolver
 */
class NotificationRecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    private NotificationRecipientResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles y permisos mínimos para que Spatie no falle
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->resolver = app(NotificationRecipientResolver::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveByRole
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_by_role_returns_users_with_that_role(): void
    {
        $admin1 = $this->createUser('admin');
        $admin2 = $this->createUser('admin');
        $dev    = $this->createUser('developer');

        $result = $this->resolver->resolveByRole('admin');

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $admin1->id));
        $this->assertTrue($result->contains('id', $admin2->id));
        $this->assertFalse($result->contains('id', $dev->id));
    }

    public function test_resolve_by_role_excludes_given_ids(): void
    {
        $admin1 = $this->createUser('admin');
        $admin2 = $this->createUser('admin');

        $result = $this->resolver->resolveByRole('admin', excludeIds: [$admin1->id]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $admin2->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveByRoles
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_by_roles_returns_union_of_roles(): void
    {
        $admin   = $this->createUser('admin');
        $manager = $this->createUser('manager');
        $dev     = $this->createUser('developer');

        $result = $this->resolver->resolveByRoles(['admin', 'manager']);

        $this->assertTrue($result->contains('id', $admin->id));
        $this->assertTrue($result->contains('id', $manager->id));
        $this->assertFalse($result->contains('id', $dev->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveProjectMembers
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_project_members_includes_owner_and_members(): void
    {
        /** @var User $owner */
        $owner  = $this->createUser('manager');
        $member = $this->createUser('developer');

        /** @var Project $project */
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id'    => $member->id,
        ]);

        $result = $this->resolver->resolveProjectMembers($project);

        $this->assertTrue($result->contains('id', $owner->id));
        $this->assertTrue($result->contains('id', $member->id));
    }

    public function test_resolve_project_members_excludes_given_ids(): void
    {
        /** @var User $owner */
        $owner  = $this->createUser('manager');
        $member = $this->createUser('developer');

        /** @var Project $project */
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        ProjectMember::factory()->create([
            'project_id' => $project->id,
            'user_id'    => $member->id,
        ]);

        $result = $this->resolver->resolveProjectMembers($project, excludeIds: [$owner->id]);

        $this->assertFalse($result->contains('id', $owner->id));
        $this->assertTrue($result->contains('id', $member->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveTaskAssignees
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_task_assignees_returns_assigned_user(): void
    {
        $assignee = $this->createUser('developer');
        /** @var Task $task */
        $task = Task::factory()->create(['assigned_to' => $assignee->id]);

        $result = $this->resolver->resolveTaskAssignees($task);

        $this->assertCount(1, $result);
        $this->assertEquals($assignee->id, $result->first()->id);
    }

    public function test_resolve_task_assignees_returns_empty_when_no_assignee(): void
    {
        /** @var Task $task */
        $task = Task::factory()->create(['assigned_to' => null]);

        $result = $this->resolver->resolveTaskAssignees($task);

        $this->assertCount(0, $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveTicketAssignees
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_ticket_assignees_returns_assigned_user(): void
    {
        $assignee = $this->createUser('developer');
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['assigned_to' => $assignee->id]);

        $result = $this->resolver->resolveTicketAssignees($ticket);

        $this->assertCount(1, $result);
        $this->assertEquals($assignee->id, $result->first()->id);
    }
}
