<?php

namespace App\Http\Requests\Objective;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'objective.edit');
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'completed'   => ['nullable', 'boolean'],
        ];
    }
}
