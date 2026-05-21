<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ticket.edit');
    }

    public function rules(): array
    {
        return [
            'subject'     => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'in:open,in_progress,resolved,closed'],
            'priority'    => ['nullable', 'in:low,medium,high,critical'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }
}
