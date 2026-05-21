<?php

namespace App\Http\Requests\Risk;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'risk.create');
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
