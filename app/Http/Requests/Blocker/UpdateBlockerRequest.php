<?php

namespace App\Http\Requests\Blocker;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('blocker.edit');
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity'    => ['nullable', 'in:low,medium,high,critical'],
        ];
    }
}
