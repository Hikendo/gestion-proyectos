<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
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
            'name'        => ['sometimes', 'string', 'max:255'],
            'code'        => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:planning,active,on_hold,completed,cancelled'],
            'start_date'  => ['nullable', 'date'],
            'end_date'    => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget'      => ['nullable', 'numeric', 'min:0'],
            'progress'    => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
