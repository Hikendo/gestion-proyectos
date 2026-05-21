<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('task.create');
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
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
