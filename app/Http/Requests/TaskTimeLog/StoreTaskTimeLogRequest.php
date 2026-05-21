<?php

namespace App\Http\Requests\TaskTimeLog;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        if (! $task instanceof Task) {
            return false;
        }

        return $this->user()->canForProject($task->project, 'task.log-time');
    }

    public function rules(): array
    {
        return [
            'minutes'     => ['required', 'integer', 'min:1', 'max:1440'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
