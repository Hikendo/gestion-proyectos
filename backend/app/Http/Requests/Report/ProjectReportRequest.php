<?php

namespace App\Http\Requests\Report;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class ProjectReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->can('view', $project);
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', 'in:last_month,last_quarter,full'],
        ];
    }

    public function period(): string
    {
        return $this->query('period', 'full');
    }
}
