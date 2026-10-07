<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

class TicketService
{
    public function create(array $data, Project $project, User $creator): Ticket
    {
        // Asignación por defecto: si no se especifica un responsable, se asigna
        // al Project Manager del proyecto (o al owner como fallback).
        $assignedTo = $data['assigned_to'] ?? $project->manager()?->id;

        return Ticket::create([
            'project_id'  => $project->id,
            'created_by'  => $creator->id,
            'assigned_to' => $assignedTo,
            'subject'     => $data['subject'],
            'description' => $data['description'] ?? null,
            'priority'    => $data['priority'] ?? 'medium',
            'status'      => 'open',
        ]);
    }
}
