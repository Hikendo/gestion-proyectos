<?php

namespace App\Http\Requests\ProjectPlan;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'project.edit')
            && ($project->owner_id === $this->user()->id
                || $this->user()->hasProjectRole($project, 'manager'));
    }

    public function rules(): array
    {
        return [
            'scope'           => ['nullable', 'string'],
            'requirements'    => ['nullable', 'string'],
            'technical_notes' => ['nullable', 'string'],
        ];
    }
}
