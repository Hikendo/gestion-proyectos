<?php

namespace App\Http\Requests\Task;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $task    = $this->route('task');

        if (! $project instanceof Project) {
            return false;
        }

        $user = $this->user();

        // PM / owner: permisos por membresía o propiedad
        if ($user->canForProject($project, 'task.edit-content')) {
            return true;
        }
        if ($user->canForProject($project, 'task.assign')) {
            return true;
        }

        // El asignado de la tarea puede editar sus propios campos
        // aunque su rol no tenga el permiso global — la policy verifica
        // assigned_to y estado Done.
        if ($task instanceof \App\Models\Task) {
            if (
                $task->assigned_to === $user->id
                && ($user->canForProject($project, 'task.edit-own')
                    || $user->canForProject($project, 'task.update-status'))
            ) {
                return true;
            }
        }

        return $user->canForProject($project, 'task.edit-own')
            || $user->canForProject($project, 'task.update-status');
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
        ];
    }
}
