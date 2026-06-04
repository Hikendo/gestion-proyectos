<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Jobs\LogActivityJob;
use App\Services\Notifications\Domain\CommentCreatedNotificationService;

class HandleCommentCreated
{
    public function __construct(
        private readonly CommentCreatedNotificationService $notificationService
    ) {}

    public function handle(CommentCreated $event): void
    {
        $this->notificationService->notify($event->comment, $event->author);

        LogActivityJob::dispatch(
            userId: $event->author->id,
            module: 'task_comment',
            action: 'created',
            data: [
                'comment_id' => $event->comment->id,
                'task_id'    => $event->comment->task_id,
            ]
        );
    }
}
