<?php

namespace App\Observers;

use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        TicketCreated::dispatch($ticket, $actor);

        if ($ticket->assigned_to && $ticket->assignee) {
            TicketAssigned::dispatch($ticket, $ticket->assignee, $actor);
        }
    }

    public function updated(Ticket $ticket): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        if ($ticket->wasChanged('assigned_to') && $ticket->assigned_to) {
            $assignee = $ticket->assignee;
            if ($assignee) {
                TicketAssigned::dispatch($ticket, $assignee, $actor);
            }
        }

        if ($ticket->wasChanged('status') && $ticket->status === 'closed') {
            TicketClosed::dispatch($ticket, $actor);
        }
    }
}
