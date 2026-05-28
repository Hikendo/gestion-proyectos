<?php

namespace App\Http\Requests\Milestone;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'milestone.edit');
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'target_date' => ['nullable', 'date'],
            'completed'   => ['nullable', 'boolean'],
        ];
    }
}
