<?php

namespace App\Http\Requests\Blocker;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'blocker.edit');
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity'    => ['nullable', 'in:low,medium,high,critical'],
        ];
    }
}
