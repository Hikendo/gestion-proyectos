<?php

namespace App\Http\Requests\ProjectPlan;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('project.edit');
    }

    public function rules(): array
    {
        return [
            'scope'           => ['nullable', 'string'],
            'requirements'    => ['nullable', 'string'],
            'technical_notes' => ['nullable', 'string'],
        ];
    }
}
