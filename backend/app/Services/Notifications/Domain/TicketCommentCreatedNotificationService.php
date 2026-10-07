<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\TicketComment;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando se agrega un comentario (seguimiento) en un ticket.
 *
 * Destinatarios:
 *  - El asignado al ticket
 *  - El creador del ticket
 *  - Excluye al autor del comentario
 * Policy check: ticket → view
 */
final class TicketCommentCreatedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'ticket_comment_created';
    }

    public function notify(TicketComment $comment, User $author): void
    {
        Log::channel('notifications')->info(
            "TicketCommentCreatedNotificationService: comentario ID {$comment->id} " .
                "en ticket ID {$comment->ticket_id} por user ID {$author->id}."
        );

        $comment->loadMissing(['ticket.assignee.fcmTokens', 'ticket.creator.fcmTokens', 'ticket.project']);
        $ticket = $comment->ticket;

        if (!$ticket) {
            Log::channel('notifications')->warning(
                "TicketCommentCreatedNotificationService: ticket no encontrado para comentario ID {$comment->id}."
            );
            return;
        }

        $candidates = collect();

        if ($ticket->assignee && $ticket->assignee->id !== $author->id) {
            $candidates->push($ticket->assignee);
        }

        if (
            $ticket->creator
            && $ticket->creator->id !== $author->id
            && !$candidates->contains('id', $ticket->creator->id)
        ) {
            $candidates->push($ticket->creator);
        }

        if ($candidates->isEmpty()) {
            return;
        }

        $authorized = $this->policyFilter->filter($candidates, 'view', $ticket);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Nuevo comentario en el ticket',
            body: "{$author->name} comentó en \"{$ticket->subject}\": " . mb_substr($comment->comment, 0, 80) . '…',
            data: [
                'type'        => $this->notificationType(),
                'comment_id'  => $comment->id,
                'ticket_id'   => $ticket->id,
                'ticket_subject' => $ticket->subject,
                'project_id'  => $ticket->project_id,
                'author_id'   => $author->id,
                'author'      => $author->name,
                'url'         => config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
            ],
            clickAction: config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
        );
    }
}
