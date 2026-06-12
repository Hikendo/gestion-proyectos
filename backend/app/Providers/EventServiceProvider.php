<?php

namespace App\Providers;

use App\Events\BlockerCreated;
use App\Events\BlockerResolved;
use App\Events\CommentCreated;
use App\Events\DeliverableApproved;
use App\Events\MilestoneCompleted;
use App\Events\ProjectCreated;
use App\Events\ProjectMemberAdded;
use App\Events\ProjectUpdated;
use App\Events\RiskDetected;
use App\Events\RoleChanged;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Listeners\HandleBlockerCreated;
use App\Listeners\HandleBlockerResolved;
use App\Listeners\HandleCommentCreated;
use App\Listeners\HandleDeliverableApproved;
use App\Listeners\HandleMilestoneCompleted;
use App\Listeners\HandleProjectCreated;
use App\Listeners\HandleProjectMemberAdded;
use App\Listeners\HandleProjectUpdated;
use App\Listeners\HandleRiskDetected;
use App\Listeners\HandleTaskAssigned;
use App\Listeners\HandleTaskCompleted;
use App\Listeners\HandleTaskCreated;
use App\Listeners\HandleTaskStatusChanged;
use App\Listeners\HandleTicketAssigned;
use App\Listeners\HandleTicketClosed;
use App\Listeners\HandleTicketCreated;
use App\Listeners\InvalidateUserSession;
use App\Models\Blocker;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Task;
use App\Models\Ticket;
use App\Observers\BlockerObserver;
use App\Observers\DeliverableObserver;
use App\Observers\MilestoneObserver;
use App\Observers\ProjectObserver;
use App\Observers\RiskObserver;
use App\Observers\TaskObserver;
use App\Observers\TicketObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ── Proyecto ──────────────────────────────────────────────────────────
        ProjectCreated::class      => [HandleProjectCreated::class],
        ProjectUpdated::class      => [HandleProjectUpdated::class],
        ProjectMemberAdded::class  => [HandleProjectMemberAdded::class],

        // ── Tarea ─────────────────────────────────────────────────────────────
        TaskCreated::class         => [HandleTaskCreated::class],
        TaskAssigned::class        => [HandleTaskAssigned::class],
        TaskStatusChanged::class   => [HandleTaskStatusChanged::class],
        TaskCompleted::class       => [HandleTaskCompleted::class],

        // ── Ticket ────────────────────────────────────────────────────────────
        TicketCreated::class       => [HandleTicketCreated::class],
        TicketAssigned::class      => [HandleTicketAssigned::class],
        TicketClosed::class        => [HandleTicketClosed::class],

        // ── Blocker / Milestone / Deliverable / Risk ───────────────────────────
        BlockerCreated::class      => [HandleBlockerCreated::class],
        BlockerResolved::class     => [HandleBlockerResolved::class],
        MilestoneCompleted::class  => [HandleMilestoneCompleted::class],
        DeliverableApproved::class => [HandleDeliverableApproved::class],
        RiskDetected::class        => [HandleRiskDetected::class],

        // ── Comentarios ───────────────────────────────────────────────────────
        CommentCreated::class      => [HandleCommentCreated::class],

        // ── Roles ─────────────────────────────────────────────────────────────
        RoleChanged::class         => [InvalidateUserSession::class],
    ];

    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        Ticket::observe(TicketObserver::class);
        Blocker::observe(BlockerObserver::class);
        Milestone::observe(MilestoneObserver::class);
        Deliverable::observe(DeliverableObserver::class);
        Risk::observe(RiskObserver::class);
    }
}
