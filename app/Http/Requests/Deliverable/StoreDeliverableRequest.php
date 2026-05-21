<?php

namespace App\Http\Requests\Deliverable;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliverableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('deliverable.create');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'delivery_date' => ['required', 'date'],
        ];
    }
}
