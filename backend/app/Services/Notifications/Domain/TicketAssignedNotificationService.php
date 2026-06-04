<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando un ticket es asignado a un usuario.
 *
 * Destinatarios:
 *  - El nuevo asignado
 *  - El creador del ticket (si es distinto del actor)
 * Policy check: ticket → view
 */
final class TicketAssignedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'ticket_assigned';
    }

    public function notify(Ticket $ticket, User $assignee, User $actor): void
    {
        Log::channel('notifications')->info(
            "TicketAssignedNotificationService: ticket ID {$ticket->id} asignado a user ID {$assignee->id}."
        );

        $ticket->loadMissing(['creator.fcmTokens', 'project']);

        $candidateIds = collect([$assignee]);

        // Notificar también al creador si es distinto del actor y del asignado
        if (
            $ticket->creator
            && $ticket->creator->id !== $actor->id
            && $ticket->creator->id !== $assignee->id
        ) {
            $ticket->creator->loadMissing('fcmTokens');
            $candidateIds->push($ticket->creator);
        }

        $authorized = $this->policyFilter->filter($candidateIds, 'view', $ticket);

        // Personalizar el mensaje para cada destinatario
        foreach ($authorized as $user) {
            if ($user->id === $assignee->id) {
                $body = "Se te asignó el ticket: \"{$ticket->subject}\".";
            } else {
                $body = "El ticket \"{$ticket->subject}\" fue asignado a {$assignee->name}.";
            }

            $this->dispatchToUser(
                user: $user,
                title: 'Ticket asignado',
                body: $body,
                data: [
                    'type'       => $this->notificationType(),
                    'ticket_id'  => $ticket->id,
                    'subject'    => $ticket->subject,
                    'project_id' => $ticket->project_id,
                    'assignee_id' => $assignee->id,
                    'assignee'   => $assignee->name,
                    'url'        => config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
                ],
                clickAction: config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
            );
        }
    }
}
