<?php

namespace App\Providers;

use App\Events\BlockerCreated;
use App\Events\BlockerResolved;
use App\Events\DeliverableApproved;
use App\Events\MilestoneCompleted;
use App\Events\ProjectCreated;
use App\Events\TaskAssigned;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Listeners\HandleBlockerCreated;
use App\Listeners\HandleBlockerResolved;
use App\Listeners\HandleDeliverableApproved;
use App\Listeners\HandleMilestoneCompleted;
use App\Listeners\HandleProjectCreated;
use App\Listeners\HandleTaskAssigned;
use App\Listeners\HandleTaskCreated;
use App\Listeners\HandleTaskStatusChanged;
use App\Listeners\HandleTicketAssigned;
use App\Listeners\HandleTicketCreated;
use App\Models\Blocker;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Observers\BlockerObserver;
use App\Observers\DeliverableObserver;
use App\Observers\MilestoneObserver;
use App\Observers\ProjectObserver;
use App\Observers\TaskObserver;
use App\Observers\TicketObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProjectCreated::class     => [HandleProjectCreated::class],
        TaskCreated::class        => [HandleTaskCreated::class],
        TaskAssigned::class       => [HandleTaskAssigned::class],
        TaskStatusChanged::class  => [HandleTaskStatusChanged::class],
        TicketCreated::class      => [HandleTicketCreated::class],
        TicketAssigned::class     => [HandleTicketAssigned::class],
        BlockerCreated::class     => [HandleBlockerCreated::class],
        BlockerResolved::class    => [HandleBlockerResolved::class],
        MilestoneCompleted::class => [HandleMilestoneCompleted::class],
        DeliverableApproved::class => [HandleDeliverableApproved::class],
    ];

    public function boot(): void
    {
        Project::observe(ProjectObserver::class);
        Task::observe(TaskObserver::class);
        Ticket::observe(TicketObserver::class);
        Blocker::observe(BlockerObserver::class);
        Milestone::observe(MilestoneObserver::class);
        Deliverable::observe(DeliverableObserver::class);
    }
}
