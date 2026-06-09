<?php

namespace App\Http\Requests\Objective;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'objective.create');
    }

    public function rules(): array
    {
        return [
            'type'        => ['required', 'in:general,specific'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'completed'   => ['nullable', 'boolean'],
        ];
    }
}
