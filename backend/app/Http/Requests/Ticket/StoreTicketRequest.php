<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ticket.create');
    }

    public function rules(): array
    {
        return [
            'subject'        => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'priority'       => ['nullable', 'in:low,medium,high,critical'],
            'assigned_to'    => ['nullable', 'exists:users,id'],
            'attachments'    => ['nullable', 'array'],
            'attachments.*'  => ['file', 'mimes:pdf,jpeg,png,zip,docx,xlsx', 'max:10240'],
        ];
    }
}
