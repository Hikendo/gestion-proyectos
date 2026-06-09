<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blocker;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Objective;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Computes editable field permissions for a given resource and user.
 *
 * The backend is the single source of truth for field-level locking.
 * This service returns a map of field_name => boolean indicating whether
 * the current user can edit that field.
 */
class FieldPermissionsService
{
    /**
     * Compute field_permissions for any supported resource.
     *
     * @return array<string, bool>
     */
    public function compute(User $user, Model $resource): array
    {
        return match (true) {
            $resource instanceof Task       => $this->forTask($user, $resource),
            $resource instanceof Ticket     => $this->forTicket($user, $resource),
            $resource instanceof Project    => $this->forProject($user, $resource),
            $resource instanceof Risk       => $this->forRisk($user, $resource),
            $resource instanceof Blocker    => $this->forBlocker($user, $resource),
            $resource instanceof Milestone  => $this->forMilestone($user, $resource),
            $resource instanceof Deliverable => $this->forDeliverable($user, $resource),
            $resource instanceof Objective  => $this->forObjective($user, $resource),
            default                         => [],
        };
    }

    /**
     * Task field permissions.
     */
    public function forTask(User $user, Task $task): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $task);
        $canUpdateStatus = Gate::forUser($user)->allows('updateStatus', $task);
        $canAssign = Gate::forUser($user)->allows('assign', $task);
        $canManageAttachments = Gate::forUser($user)->allows('manageAttachments', $task);
        $canLogTime = Gate::forUser($user)->allows('logTime', $task);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'status'             => $canUpdateStatus,
            'priority'           => $canEdit,
            'due_date'           => $canEdit,
            'estimated_hours'    => $canEdit,
            'progress'           => $canAssign || ($task->assigned_to === $user->id),
            'assigned_to'        => $canAssign,
            'attachments'        => $canManageAttachments,
            'log_time'           => $canLogTime,
        ];
    }

    /**
     * Ticket field permissions.
     */
    public function forTicket(User $user, Ticket $ticket): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $ticket);
        $canAssign = Gate::forUser($user)->allows('assign', $ticket);
        $canManageAttachments = Gate::forUser($user)->allows('manageAttachments', $ticket);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'status'             => $canAssign || ($ticket->assigned_to === $user->id),
            'priority'           => $canEdit,
            'category'           => $canEdit,
            'assigned_to'        => $canAssign,
            'attachments'        => $canManageAttachments,
        ];
    }

    /**
     * Project field permissions.
     */
    public function forProject(User $user, Project $project): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $project);
        $canManageAttachments = Gate::forUser($user)->allows('manageAttachments', $project);

        return [
            'name'               => $canEdit,
            'code'               => $canEdit,
            'description'        => $canEdit,
            'status'             => $canEdit,
            'start_date'         => $canEdit,
            'end_date'           => $canEdit,
            'budget'             => $canEdit,
            'progress'           => $canEdit,
            'attachments'        => $canManageAttachments,
        ];
    }

    /**
     * Risk field permissions.
     */
    public function forRisk(User $user, Risk $risk): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $risk);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'probability'        => $canEdit,
            'impact'             => $canEdit,
            'status'             => $canEdit,
            'mitigation_plan'    => $canEdit,
        ];
    }

    /**
     * Blocker field permissions.
     */
    public function forBlocker(User $user, Blocker $blocker): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $blocker);
        $canResolve = Gate::forUser($user)->allows('resolve', $blocker);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'severity'           => $canEdit,
            'resolved'           => $canResolve,
            'resolution_notes'   => $canResolve,
        ];
    }

    /**
     * Milestone field permissions.
     */
    public function forMilestone(User $user, Milestone $milestone): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $milestone);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'due_date'           => $canEdit,
            'completed'          => $canEdit,
        ];
    }

    /**
     * Deliverable field permissions.
     */
    public function forDeliverable(User $user, Deliverable $deliverable): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $deliverable);
        $canApprove = Gate::forUser($user)->allows('approve', $deliverable);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'due_date'           => $canEdit,
            'approved'           => $canApprove,
        ];
    }

    /**
     * Objective field permissions.
     */
    public function forObjective(User $user, Objective $objective): array
    {
        $canEdit = Gate::forUser($user)->allows('update', $objective);

        return [
            'title'              => $canEdit,
            'description'        => $canEdit,
            'type'               => $canEdit,
            'target_value'       => $canEdit,
            'current_value'      => $canEdit,
        ];
    }
}