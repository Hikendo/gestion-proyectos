<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permiso mínimo: task.edit o task.update-status
        // La lógica fina (solo su tarea) se resuelve en TaskService::canEdit()
        return $this->user()->canAny(['task.edit', 'task.update-status', 'task.assign']);
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
