<?php

namespace App\Http\Requests\ProjectPhase;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            $project = Project::find($project);
        }

        // Autorización por-proyecto (Manager/owner). El controlador además
        // exige authorize('update', $project) vía ProjectPolicy.
        return $project instanceof Project
            && $this->user()->canForProject($project, 'phase.edit');
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'status'     => ['nullable', 'in:planned,in_progress'],
        ];
    }
}
