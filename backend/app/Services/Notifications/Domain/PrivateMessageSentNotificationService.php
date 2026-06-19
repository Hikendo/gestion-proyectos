<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\DirectMessage;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Collection;

class PrivateMessageSentNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'new_private_message';
    }

    /**
     * Notify the other participant in the conversation.
     */
    public function notify(DirectMessage $message): void
    {
        $conversation = $message->conversation;
        $recipient = $conversation->otherUser($message->user);

        if (!$recipient) {
            return;
        }

        $this->dispatchToUser(
            user: $recipient,
            title: 'Nuevo mensaje privado',
            body: "{$message->user->name} te ha enviado un mensaje privado",
            data: [
                'project_id' => $conversation->project_id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'sender_name' => $message->user->name,
            ],
            clickAction: null,
            icon: null,
            image: null,
        );
    }
}