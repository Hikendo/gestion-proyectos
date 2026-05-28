<?php

namespace App\Http\Requests\Blocker;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreBlockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'blocker.create');
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity'    => ['required', 'in:low,medium,high,critical'],
            'task_id'     => ['nullable', 'exists:tasks,id'],
        ];
    }
}
