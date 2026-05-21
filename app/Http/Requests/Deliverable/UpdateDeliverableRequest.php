<?php

namespace App\Http\Requests\Deliverable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliverableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('deliverable.edit');
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'delivery_date' => ['nullable', 'date'],
        ];
    }
}
