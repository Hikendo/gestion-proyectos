<?php

namespace App\Http\Requests\Deliverable;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliverableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'deliverable.create');
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
