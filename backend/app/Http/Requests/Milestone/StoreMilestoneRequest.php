<?php

namespace App\Http\Requests\Milestone;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'milestone.create');
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'target_date' => ['required', 'date'],
            'completed'   => ['nullable', 'boolean'],
        ];
    }
}
