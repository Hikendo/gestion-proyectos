<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketClosed;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\TicketClosedNotificationService;

class HandleTicketClosed
{
    public function __construct(
        private readonly TicketClosedNotificationService $notificationService
    ) {}

    public function handle(TicketClosed $event): void
    {
        $this->notificationService->notify($event->ticket, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'ticket',
            action: 'closed',
            data: [
                'ticket_id'  => $event->ticket->id,
                'subject'    => $event->ticket->subject,
                'project_id' => $event->ticket->project_id,
            ]
        );
    }
}