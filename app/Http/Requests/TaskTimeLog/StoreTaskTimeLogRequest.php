<?php

namespace App\Http\Requests\TaskTimeLog;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('task.log-time');
    }

    public function rules(): array
    {
        return [
            'minutes'     => ['required', 'integer', 'min:1', 'max:1440'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
