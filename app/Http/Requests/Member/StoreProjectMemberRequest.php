<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('project.assign-members');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'role'    => ['required', 'in:manager,developer,qa,support,client'],
        ];
    }
}
