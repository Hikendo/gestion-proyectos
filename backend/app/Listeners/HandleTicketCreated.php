<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Services\Notifications\Domain\TicketCreatedNotificationService;

class HandleTicketCreated
{
    public function __construct(
        private readonly TicketCreatedNotificationService $notificationService
    ) {}

    public function handle(TicketCreated $event): void
    {
        $this->notificationService->notify($event->ticket, $event->actor);

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
