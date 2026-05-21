<?php

namespace App\Http\Requests\Objective;

use Illuminate\Foundation\Http\FormRequest;

class StoreObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('objective.create');
    }

    public function rules(): array
    {
        return [
            'type'        => ['required', 'in:general,specific'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
