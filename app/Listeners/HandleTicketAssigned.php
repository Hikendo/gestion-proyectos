<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Jobs\LogActivityJob;
use App\Notifications\TicketAssignedNotification;

class HandleTicketAssigned
{
    public function handle(TicketAssigned $event): void
    {
        $event->assignee->notify(new TicketAssignedNotification($event->ticket));

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
