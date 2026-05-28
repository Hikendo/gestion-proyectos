<?php

namespace App\Http\Requests\ProjectPhase;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectPhaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('phase.edit');
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'progress'   => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}
