<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\TicketAssignedNotificationService;

class HandleTicketAssigned
{
    public function __construct(
        private readonly TicketAssignedNotificationService $notificationService
    ) {}

    public function handle(TicketAssigned $event): void
    {
        $this->notificationService->notify($event->ticket, $event->assignee, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'ticket',
            action: 'assigned',
            data: [
                'ticket_id'   => $event->ticket->id,
                'subject'     => $event->ticket->subject,
                'assigned_to' => $event->assignee->id,
                'assignee'    => $event->assignee->name,
            ]
        );
    }
}
