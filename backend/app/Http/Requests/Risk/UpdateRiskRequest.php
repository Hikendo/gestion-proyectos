<?php

namespace App\Http\Requests\Risk;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'risk.edit');
    }

    public function rules(): array
    {
        return [
            'title'           => ['sometimes', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'impact'          => ['nullable', 'in:low,medium,high,critical'],
            'probability'     => ['nullable', 'in:low,medium,high'],
            'mitigation_plan' => ['nullable', 'string'],
            'phase_id'        => ['nullable', 'exists:project_phases,id'],
        ];
    }
}
