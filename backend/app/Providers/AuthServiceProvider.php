<?php

namespace App\Providers;

use App\Models\Blocker;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Objective;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\BlockerPolicy;
use App\Policies\DeliverablePolicy;
use App\Policies\MilestonePolicy;
use App\Policies\ObjectivePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RiskPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Project::class     => ProjectPolicy::class,
        Task::class        => TaskPolicy::class,
        Ticket::class      => TicketPolicy::class,
        Risk::class        => RiskPolicy::class,
        Blocker::class     => BlockerPolicy::class,
        Milestone::class   => MilestonePolicy::class,
        Deliverable::class => DeliverablePolicy::class,
        Objective::class   => ObjectivePolicy::class,
        User::class => UserPolicy::class,

    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
