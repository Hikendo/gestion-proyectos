<?php

namespace App\Http\Requests\Task;

use App\Enums\PhaseStatus;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'task.create');
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
            'attachments'     => ['nullable', 'array'],
            'attachments.*'   => ['file', 'mimes:pdf,jpeg,png,zip,docx,xlsx', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $phaseId = $this->input('phase_id');
                if (! $phaseId) {
                    return;
                }

                $phase = ProjectPhase::find($phaseId);
                if (! $phase) {
                    return;
                }

                // Fase con end_date nula es de mantenimiento — siempre permite crear tareas
                if ($phase->end_date === null) {
                    return;
                }

                // Fase completada: no se pueden crear nuevas tareas
                if ($phase->status === PhaseStatus::Completed) {
                    $validator->errors()->add(
                        'phase_id',
                        'No se pueden crear tareas en una fase completada.'
                    );
                    return;
                }

                // Fase vencida (end_date < now) y no completada
                if ($phase->end_date->isPast()) {
                    $validator->errors()->add(
                        'phase_id',
                        'No se pueden crear tareas en una fase vencida. Extienda la fecha de fin o use otra fase.'
                    );
                }
            },
        ];
    }
}
