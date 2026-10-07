<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCommentCreated;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\TicketCommentCreatedNotificationService;

class HandleTicketCommentCreated
{
    public function __construct(
        private readonly TicketCommentCreatedNotificationService $notificationService
    ) {}

    public function handle(TicketCommentCreated $event): void
    {
        $this->notificationService->notify($event->comment, $event->author);

        LogActivityJob::dispatch(
            userId: $event->author->id,
            module: 'ticket_comment',
            action: 'created',
            data: [
                'comment_id' => $event->comment->id,
                'ticket_id'  => $event->comment->ticket_id,
            ]
        );
    }
}
