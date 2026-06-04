<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\NotificationRecipientResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
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

        // Ejecutar el seeder directamente (mismo proceso/transacción) para
        // evitar que artisan corra en una conexión separada y los roles no
        // sean visibles en MySQL + RefreshDatabase.
        $seeder = app(RolesAndPermissionsSeeder::class);
        $seeder->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->resolver = app(NotificationRecipientResolver::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveByRole
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_by_role_returns_users_with_that_role(): void
    {
        $dev1 = $this->createUser('developer');
        $dev2 = $this->createUser('developer');
        $qa   = $this->createUser('qa');

        $result = $this->resolver->resolveByRole('developer');

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $dev1->id));
        $this->assertTrue($result->contains('id', $dev2->id));
        $this->assertFalse($result->contains('id', $qa->id));
    }

    public function test_resolve_by_role_excludes_given_ids(): void
    {
        $dev1 = $this->createUser('developer');
        $dev2 = $this->createUser('developer');

        $result = $this->resolver->resolveByRole('developer', excludeIds: [$dev1->id]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $dev2->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveByRoles
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_by_roles_returns_union_of_roles(): void
    {
        $pm  = $this->createUser('project-manager');
        $dev = $this->createUser('developer');
        $qa  = $this->createUser('qa');

        $result = $this->resolver->resolveByRoles(['project-manager', 'developer']);

        $this->assertTrue($result->contains('id', $pm->id));
        $this->assertTrue($result->contains('id', $dev->id));
        $this->assertFalse($result->contains('id', $qa->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // resolveProjectMembers
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resolve_project_members_includes_owner_and_members(): void
    {
        /** @var User $owner */
        $owner  = $this->createUser('project-manager');
        $member = $this->createUser('developer');

        /** @var Project $project */
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $member->id,
            'role'       => 'developer',
        ]);

        $result = $this->resolver->resolveProjectMembers($project);

        $this->assertTrue($result->contains('id', $owner->id));
        $this->assertTrue($result->contains('id', $member->id));
    }

    public function test_resolve_project_members_excludes_given_ids(): void
    {
        /** @var User $owner */
        $owner  = $this->createUser('project-manager');
        $member = $this->createUser('developer');

        /** @var Project $project */
        $project = Project::factory()->create(['owner_id' => $owner->id]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $member->id,
            'role'       => 'developer',
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
