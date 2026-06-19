<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\Notifications\Domain\GroupMessageSentNotificationService;

class HandleGroupMessageSent
{
    public function __construct(
        private GroupMessageSentNotificationService $notificationService,
    ) {}

    public function handle(MessageSent $event): void
    {
        $this->notificationService->notify($event->message);
    }
}