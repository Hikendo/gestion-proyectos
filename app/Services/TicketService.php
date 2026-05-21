<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

class TicketService
{
    public function create(array $data, Project $project, User $creator): Ticket
    {
        return Ticket::create([
            'project_id'  => $project->id,
            'created_by'  => $creator->id,
            'assigned_to' => $data['assigned_to'] ?? null,
            'subject'     => $data['subject'],
            'description' => $data['description'] ?? null,
            'priority'    => $data['priority'] ?? 'medium',
            'status'      => 'open',
        ]);
    }
}
