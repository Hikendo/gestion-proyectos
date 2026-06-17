<?php

namespace App\Services;

use App\Exceptions\ProjectException;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMetric;
use App\Models\User;
use App\Traits\HasActivityLog;
use App\Traits\HasMetrics;
use App\Traits\HasProjectAccess;

class ProjectService
{
    use HasActivityLog, HasMetrics, HasProjectAccess;

    public function create(array $data, User $owner): Project
    {
        if (isset($data['code']) && Project::where('code', $data['code'])->exists()) {
            throw ProjectException::duplicateCode($data['code']);
        }

        $data['owner_id'] = $owner->id;
        $data['status']   = $data['status'] ?? 'planning';
        $data['progress'] = 0;

        $project = Project::create($data);

        $project->members()->create([
            'user_id' => $owner->id,
            'role'    => 'manager',
        ]);

        ProjectMetric::create(['project_id' => $project->id]);

        return $project;
    }

    // Retorna ProjectMember en lugar de void
    public function addMember(Project $project, int $userId, string $role): ProjectMember
    {
        $this->assertProjectIsOpen($project);

        if ($project->members()->where('user_id', $userId)->exists()) {
            throw ProjectException::memberAlreadyExists();
        }

        return $project->members()->create([
            'user_id' => $userId,
            'role'    => $role,
        ]);
    }

    public function updateMember(Project $project, int $userId, string $role): ProjectMember
    {
        $member = $project->members()->where('user_id', $userId)->first();

        if (! $member) {
            throw ProjectException::memberNotFound();
        }

        if ($project->owner_id === $userId) {
            throw ProjectException::cannotChangeOwnerRole();
        }

        $member->update(['role' => $role]);

        return $member;
    }

    public function suspendMember(Project $project, int $userId): ProjectMember
    {
        $member = $project->members()->where('user_id', $userId)->first();

        if (! $member) {
            throw ProjectException::memberNotFound();
        }

        if ($member->suspended_at) {
            throw ProjectException::memberAlreadySuspended();
        }

        $member->update(['suspended_at' => now()]);

        return $member;
    }

    public function unsuspendMember(Project $project, int $userId): ProjectMember
    {
        $member = $project->members()->where('user_id', $userId)->first();

        if (! $member) {
            throw ProjectException::memberNotFound();
        }

        if (! $member->suspended_at) {
            throw ProjectException::memberNotSuspended();
        }

        $member->update(['suspended_at' => null]);

        return $member;
    }

    public function removeMember(Project $project, int $userId): void
    {
        if ($project->owner_id === $userId) {
            throw ProjectException::cannotRemoveOwner();
        }

        $project->members()->where('user_id', $userId)->delete();
    }

    public function canAccess(User $user, Project $project): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $project->owner_id === $user->id
            || $project->members()->where('user_id', $user->id)->exists();
    }
}
