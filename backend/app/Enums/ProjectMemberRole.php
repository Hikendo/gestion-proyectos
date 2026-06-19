<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case Manager   = 'manager';
    case Developer = 'developer';
    case Qa        = 'qa';
    case Support   = 'support';
    case Client    = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Manager   => 'Project Manager',
            self::Developer => 'Developer',
            self::Qa        => 'QA',
            self::Support   => 'Support',
            self::Client    => 'Cliente',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::Manager => [
                'dashboard.view',
                'project.view',
                'project.create',
                'project.edit',
                'project.assign-members',
                'project.manage-attachments',
                'phase.view',
                'phase.create',
                'phase.edit',
                'phase.delete',
                'task.view',
                'task.create',
                'task.edit-content',
                'task.delete',
                'task.assign',
                'task.update-status',
                'task.log-time',
                'task.manage-attachments',
                'ticket.view',
                'ticket.create',
                'ticket.edit-any',
                'ticket.delete',
                'ticket.assign',
                'ticket.manage-attachments',
                'risk.view',
                'risk.create',
                'risk.edit',
                'risk.delete',
                'blocker.view',
                'blocker.create',
                'blocker.edit',
                'blocker.resolve',
                'milestone.view',
                'milestone.create',
                'milestone.edit',
                'milestone.delete',
                'deliverable.view',
                'deliverable.create',
                'deliverable.edit',
                'deliverable.approve',
                'objective.view',
                'objective.create',
                'objective.edit',
                'metrics.view',
                'reports.view',
            ],
            self::Developer => [
                'dashboard.view',
                'project.view',
                'task.view',
                'task.create',
                'task.edit-own',
                'task.update-status',
                'task.log-time',
                'ticket.view',
                'ticket.create',
                'ticket.edit-own',
                'risk.view',
                'blocker.view',
                'blocker.create',
                'milestone.view',
                'deliverable.view',
                'objective.view',
                'metrics.view',
            ],
            self::Qa => [
                'dashboard.view',
                'project.view',
                'task.view',
                'task.create',
                'task.edit-own',
                'task.update-status',
                'ticket.view',
                'ticket.create',
                'ticket.edit-own',
                'risk.view',
                'blocker.view',
                'blocker.create',
                'milestone.view',
                'deliverable.view',
                'objective.view',
                'metrics.view',
            ],
            self::Support => [
                'dashboard.view',
                'project.view',
                'ticket.view',
                'ticket.create',
                'ticket.edit-own',
                'ticket.assign',
                'blocker.view',
                'user.view',
            ],
            self::Client => [
                'dashboard.view',
                'project.view',
                'ticket.view',
                'ticket.create',
                'ticket.edit-own',
                'milestone.view',
                'deliverable.view',
                'objective.view',
                'metrics.view',
                'reports.view',
            ],
        };
    }

    public static function permissionsFor(string|self $role): array
    {
        return ($role instanceof self ? $role : self::from($role))->permissions();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
