<?php

namespace App\Http\Requests\Blocker;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blocker.create');
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
