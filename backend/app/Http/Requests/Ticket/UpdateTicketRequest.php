<?php

namespace App\Http\Requests\Ticket;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización fina (edit-any vs edit-own, estado closed) la aplica
        // TicketController::update() vía TicketPolicy::update() — que es
        // project-scoped (canForProject) — tras assertBelongsToProject().
        // Delegamos en la policy para no duplicar la lógica aquí.
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        return [
            'subject'     => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:open,in_progress,resolved,closed'],
            'priority'    => ['nullable', 'in:low,medium,high,critical'],
            // El responsable debe ser miembro del proyecto y NO puede ser un
            // cliente (los clientes no atienden tickets, solo los reportan).
            'assigned_to' => [
                'nullable',
                Rule::exists('project_members', 'user_id')
                    ->where('project_id', $project?->id)
                    ->whereNot('role', 'client'),
            ],
        ];
    }
}
