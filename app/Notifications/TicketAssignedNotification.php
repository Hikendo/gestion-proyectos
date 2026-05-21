<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'ticket_assigned',
            'ticket_id'  => $this->ticket->id,
            'subject'    => $this->ticket->subject,
            'project_id' => $this->ticket->project_id,
            'priority'   => $this->ticket->priority->value,
            'message'    => "Se te asignó el ticket: {$this->ticket->subject}",
        ];
    }
}
