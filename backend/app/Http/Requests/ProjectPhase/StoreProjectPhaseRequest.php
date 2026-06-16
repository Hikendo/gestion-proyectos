<?php

namespace App\Http\Requests\ProjectPhase;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('phase.create');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'status'     => ['nullable', 'in:planned,in_progress'],
        ];
    }
}
