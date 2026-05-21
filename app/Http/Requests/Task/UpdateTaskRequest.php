<?php

namespace App\Http\Requests\Task;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'task.edit')
            || $this->user()->canForProject($project, 'task.update-status')
            || $this->user()->canForProject($project, 'task.assign');
    }

    public function rules(): array
    {
        return [
            'title'           => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'phase_id'        => ['nullable', 'exists:project_phases,id'],
            'assigned_to'     => ['nullable', 'exists:users,id'],
            'priority'        => ['nullable', 'in:low,medium,high,critical'],
            'status'          => ['nullable', 'in:pending,in_progress,review,done,blocked'],
            'due_date'        => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'progress'        => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
