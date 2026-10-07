<?php

namespace App\Http\Requests\Ticket;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        // Autorización por-proyecto (coherente con TicketPolicy y el resto de
        // FormRequests). canForProject aplica bypass de super-admin/owner y
        // valida el permiso 'ticket.create' según el rol de membresía.
        return $project instanceof Project
            && $this->user()->canForProject($project, 'ticket.create');
    }

    public function rules(): array
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        return [
            'subject'        => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'priority'       => ['nullable', 'in:low,medium,high,critical'],
            // El responsable debe ser miembro del proyecto y NO puede ser un
            // cliente (los clientes no atienden tickets, solo los reportan).
            'assigned_to'    => [
                'nullable',
                Rule::exists('project_members', 'user_id')
                    ->where('project_id', $project?->id)
                    ->whereNot('role', 'client'),
            ],
            'attachments'    => ['nullable', 'array'],
            'attachments.*'  => ['file', 'mimes:pdf,jpeg,png,zip,docx,xlsx', 'max:10240'],
        ];
    }
}
