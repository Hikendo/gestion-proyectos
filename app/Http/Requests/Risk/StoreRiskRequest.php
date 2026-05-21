<?php

namespace App\Http\Requests\Risk;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('risk.create');
    }

    public function rules(): array
    {
        return [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'impact'          => ['required', 'in:low,medium,high,critical'],
            'probability'     => ['required', 'in:low,medium,high'],
            'mitigation_plan' => ['nullable', 'string'],
        ];
    }
}
