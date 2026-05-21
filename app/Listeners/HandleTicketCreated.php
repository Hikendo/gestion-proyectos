<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;

class HandleTicketCreated
{
    public function handle(TicketCreated $event): void
    {
        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'ticket',
            action: 'created',
            data: [
                'ticket_id'  => $event->ticket->id,
                'subject'    => $event->ticket->subject,
                'project_id' => $event->ticket->project_id,
                'priority'   => $event->ticket->priority->value,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->ticket->project_id);
    }
}
