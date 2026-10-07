<?php

namespace App\Http\Requests\TicketComment;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        $ticket = $this->route('ticket');
        if (! $ticket instanceof Ticket) {
            $ticket = Ticket::find($ticket);
        }

        if (! $project instanceof Project || ! $ticket instanceof Ticket) {
            return false;
        }

        $user = $this->user();

        // Debe poder ver el ticket dentro del proyecto.
        if (! $user->canForProject($project, 'ticket.view')) {
            return false;
        }

        // Un cliente solo puede comentar en sus propios tickets.
        if ($user->hasProjectRole($project, 'client')) {
            return $ticket->created_by === $user->id;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
        ];
    }
}
