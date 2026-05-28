<?php

namespace App\Http\Requests\Member;

use App\Enums\ProjectMemberRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->hasRole('super-admin')
            || $project->owner_id === $this->user()->id
            || $this->user()->hasProjectRole($project, 'manager');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', Rule::in(ProjectMemberRole::values())],
        ];
    }
}
