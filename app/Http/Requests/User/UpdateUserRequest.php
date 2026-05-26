<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $isSelf  = $this->user()->id === (int) $this->route('user')->id;
        $isAdmin = $this->user()->hasRole('super-admin');

        return $isSelf || $isAdmin;
    }

    public function rules(): array
    {
        $rules = [
            'name'     => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];

        if ($this->user()->hasRole('super-admin')) {
            $rules['role'] = ['sometimes', 'nullable', 'string', 'in:super-admin,project-manager'];
        }

        return $rules;
    }
}
