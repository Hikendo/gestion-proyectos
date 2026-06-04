<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando se crea un nuevo ticket.
 *
 * Destinatarios: todos los miembros del proyecto con permiso 'ticket.view'
 *                (o rol manager/PM) excepto el creador.
 * Policy check: ticket → view
 */
final class TicketCreatedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'ticket_created';
    }

    public function notify(Ticket $ticket, User $actor): void
    {
        Log::channel('notifications')->info(
            "TicketCreatedNotificationService: ticket ID {$ticket->id} creado por user ID {$actor->id}."
        );

        $ticket->loadMissing('project');

        $candidates = $this->resolver->resolveProjectMembers(
            project: $ticket->project,
            excludeIds: [$actor->id],
        );

        $authorized = $this->policyFilter->filter($candidates, 'view', $ticket);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Nuevo ticket creado',
            body: "Se creó el ticket: \"{$ticket->subject}\" en el proyecto \"{$ticket->project->name}\".",
            data: [
                'type'        => $this->notificationType(),
                'ticket_id'   => $ticket->id,
                'subject'     => $ticket->subject,
                'project_id'  => $ticket->project_id,
                'priority'    => $ticket->priority?->value,
                'url'         => config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
            ],
            clickAction: config('app.url') . "/projects/{$ticket->project_id}/tickets/{$ticket->id}",
        );
    }
}
