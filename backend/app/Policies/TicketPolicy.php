<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super-admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ticket.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.view');
    }

    public function create(User $user): bool
    {
        return $user->can('ticket.create');
    }

    /**
     * Reglas de edición de tickets:
     *
     * - Nadie puede editar un ticket cerrado.
     * - PM / Owner: puede editar CUALQUIER ticket (usa ticket.edit-any).
     * - Developer / QA / Support / Client: solo puede editar tickets PROPIOS
     *   (usa ticket.edit-own) y solo si el ticket está en estado Open.
     * - Client: además, no puede editar tickets que ya están "In Progress"
     *   o "Resolved" para evitar alteración del alcance acordado.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status->isClosed()) {
            return false;
        }

        // PM / Owner: puede editar cualquier ticket
        if ($ticket->project->owner_id === $user->id || $user->hasProjectRole($ticket->project, 'manager')) {
            return $user->canForProject($ticket->project, 'ticket.edit-any');
        }

        // Client: solo tickets propios y solo si están en estado Open
        if ($user->hasProjectRole($ticket->project, 'client')) {
            return $user->canForProject($ticket->project, 'ticket.edit-own')
                && $ticket->created_by === $user->id
                && $ticket->status->isOpen();
        }

        // Developer / QA / Support: solo tickets propios
        return $user->canForProject($ticket->project, 'ticket.edit-own')
            && $ticket->created_by === $user->id;
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.assign');
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.delete');
    }

    /**
     * Gestión de adjuntos de ticket.
     * Solo PM/owner pueden subir/eliminar adjuntos en tickets del proyecto.
     */
    public function manageAttachments(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.manage-attachments')
            && ($ticket->project->owner_id === $user->id
                || $user->hasProjectRole($ticket->project, 'manager'));
    }
}
