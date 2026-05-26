<?php

namespace App\Models;

use App\Enums\TicketStatus;
use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Ticket extends Model
{
    use HasFactory, Searchable;

    public function toSearchableArray(): array
    {
        return [
            'subject'     => $this->subject,
            'description' => $this->description,
        ];
    }

    protected $fillable = [
        'project_id',
        'created_by',
        'assigned_to',
        'subject',
        'description',
        'status',
        'priority',
    ];

    protected $casts = [
        'due_date'  => 'date',
        'status'    => TicketStatus::class,   // ← corregido
        'priority'  => TicketPriority::class, // ← corregido (o TaskPriority si no existe TicketPriority)
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
