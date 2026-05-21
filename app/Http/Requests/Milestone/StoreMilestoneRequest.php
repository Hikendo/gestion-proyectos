<?php

namespace App\Http\Requests\Milestone;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('milestone.create');
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'target_date' => ['required', 'date'],
        ];
    }
}
