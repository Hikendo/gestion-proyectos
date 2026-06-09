<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

final class TicketClosedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'ticket_closed';
    }

    public function notify(Ticket $ticket, User $actor): void
    {
        Log::channel('notifications')->info(
            "TicketClosedNotificationService: ticket ID {$ticket->id} cerrado por user ID {$actor->id}."
        );

        $candidates = collect();

        if ($ticket->creator && $ticket->creator->id !== $actor->id) {
            $ticket->creator->loadMissing('fcmTokens');
            $candidates->push($ticket->creator);
        }

        if ($ticket->assignee && $ticket->assignee->id !== $actor->id && ! $candidates->contains('id', $ticket->assignee->id)) {
            $ticket->assignee->loadMissing('fcmTokens');
            $candidates->push($ticket->assignee);
        }

        if ($candidates->isEmpty()) {
            return;
        }

        $authorized = $this->policyFilter->filter($candidates, 'view', $ticket);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Ticket cerrado',
            body: "El ticket \"{$ticket->subject}\" fue cerrado.",
            data: [
                'type'       => $this->notificationType(),
                'ticket_id'  => $ticket->id,
                'subject'    => $ticket->subject,
                'project_id' => $ticket->project_id,
                'url'        => config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
            ],
            clickAction: config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
        );
    }
}