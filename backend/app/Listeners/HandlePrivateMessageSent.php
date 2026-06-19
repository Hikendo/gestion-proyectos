<?php

namespace App\Listeners;

use App\Events\DirectMessageSent;
use App\Services\Notifications\Domain\PrivateMessageSentNotificationService;

class HandlePrivateMessageSent
{
    public function __construct(
        private PrivateMessageSentNotificationService $notificationService,
    ) {}

    public function handle(DirectMessageSent $event): void
    {
        $this->notificationService->notify($event->message);
    }
}