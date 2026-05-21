<?php

namespace App\Http\Requests\Risk;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('risk.edit');
    }

    public function rules(): array
    {
        return [
            'title'           => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'impact'          => ['nullable', 'in:low,medium,high,critical'],
            'probability'     => ['nullable', 'in:low,medium,high'],
            'mitigation_plan' => ['nullable', 'string'],
        ];
    }
}
