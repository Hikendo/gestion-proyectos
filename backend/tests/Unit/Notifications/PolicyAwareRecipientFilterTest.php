<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\Notifications\PolicyAwareRecipientFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @covers \App\Services\Notifications\PolicyAwareRecipientFilter
 */
class PolicyAwareRecipientFilterTest extends TestCase
{
    use RefreshDatabase;

    private PolicyAwareRecipientFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles y permisos mínimos para que Spatie no falle
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        $this->filter = new PolicyAwareRecipientFilter();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Usuarios sin permiso de vista sobre el recurso
    // ─────────────────────────────────────────────────────────────────────────

    public function test_filters_out_users_without_view_permission(): void
    {
        $member   = $this->createUser('developer');
        $outsider = $this->createUser('developer');

        /** @var Project $project */
        $project = Project::factory()->create();
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $member->id,
            'role'       => 'developer',
        ]);
        // outsider no es miembro del proyecto → no puede verlo

        $users  = collect([$member, $outsider]);
        $result = $this->filter->filter($users, 'view', $project);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $member->id));
        $this->assertFalse($result->contains('id', $outsider->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Colección vacía
    // ─────────────────────────────────────────────────────────────────────────

    public function test_empty_collection_returns_empty(): void
    {
        /** @var Project $project */
        $project = Project::factory()->create();

        $result = $this->filter->filter(collect(), 'view', $project);

        $this->assertCount(0, $result);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Super-admin bypass sin ser miembro (solo tiene rol global)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_super_admin_bypasses_policy_check(): void
    {
        $sa = $this->createUser('super-admin');

        /** @var Project $project */
        $project = Project::factory()->create();
        // super-admin NO es miembro del proyecto → pero el before() retorna true

        $users  = collect([$sa]);
        $result = $this->filter->filter($users, 'view', $project);

        $this->assertCount(1, $result);
        $this->assertTrue($result->contains('id', $sa->id));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Todos autorizados cuando todos tienen permiso
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_users_pass_when_all_have_permission(): void
    {
        $member1 = $this->createUser('developer');
        $member2 = $this->createUser('developer');

        /** @var Project $project */
        $project = Project::factory()->create();
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $member1->id,
            'role'       => 'developer',
        ]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $member2->id,
            'role'       => 'developer',
        ]);

        $users  = collect([$member1, $member2]);
        $result = $this->filter->filter($users, 'view', $project);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $member1->id));
        $this->assertTrue($result->contains('id', $member2->id));
    }
}
