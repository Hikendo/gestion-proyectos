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
        return $user->canForProject($ticket->project, 'ticket.view')
            && ($ticket->project->owner_id === $user->id
                || $ticket->project->members()->where('user_id', $user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->can('ticket.create');
    }

    /**
     * No se puede editar un ticket cerrado.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->status->isClosed()) {
            return false;
        }

        return $user->canForProject($ticket->project, 'ticket.edit');
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.assign');
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->canForProject($ticket->project, 'ticket.delete');
    }
}
