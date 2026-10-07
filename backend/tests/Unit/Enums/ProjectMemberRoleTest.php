<?php

namespace Tests\Unit\Enums;

use App\Enums\ProjectMemberRole;
use PHPUnit\Framework\TestCase;

class ProjectMemberRoleTest extends TestCase
{
    public function test_permissions_for_valid_role_returns_its_permissions(): void
    {
        $this->assertSame(
            ProjectMemberRole::Support->permissions(),
            ProjectMemberRole::permissionsFor('support'),
        );
    }

    public function test_permissions_for_accepts_enum_instance(): void
    {
        $this->assertSame(
            ProjectMemberRole::Manager->permissions(),
            ProjectMemberRole::permissionsFor(ProjectMemberRole::Manager),
        );
    }

    /**
     * 'analyst' era el nombre antiguo de 'support'. Si aparece en datos
     * heredados en producción no debe lanzar \ValueError (regresión del 500).
     */
    public function test_permissions_for_legacy_analyst_role_returns_empty_array(): void
    {
        $this->assertSame([], ProjectMemberRole::permissionsFor('analyst'));
    }

    public function test_permissions_for_null_returns_empty_array(): void
    {
        $this->assertSame([], ProjectMemberRole::permissionsFor(null));
    }

    public function test_permissions_for_unknown_role_returns_empty_array(): void
    {
        $this->assertSame([], ProjectMemberRole::permissionsFor('does-not-exist'));
    }

    public function test_values_matches_canonical_role_set(): void
    {
        $this->assertSame(
            ['manager', 'developer', 'qa', 'support', 'client'],
            ProjectMemberRole::values(),
        );
    }
}
