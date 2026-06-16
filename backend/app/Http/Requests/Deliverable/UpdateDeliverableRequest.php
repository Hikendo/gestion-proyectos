<?php

namespace App\Http\Requests\Deliverable;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliverableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'deliverable.edit');
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'delivery_date' => ['nullable', 'date'],
            'phase_id'      => ['nullable', 'exists:project_phases,id'],
            'parent_id'     => ['nullable', 'exists:deliverables,id'],
        ];
    }
}
