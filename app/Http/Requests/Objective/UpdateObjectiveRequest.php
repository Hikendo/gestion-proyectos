<?php

namespace App\Http\Requests\Objective;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('objective.edit');
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'completed'   => ['nullable', 'boolean'],
        ];
    }
}
