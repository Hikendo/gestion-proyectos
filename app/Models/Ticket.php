<?php

namespace App\Models;

use App\Enums\TicketStatus;   // ← era TaskStatus
use App\Enums\TicketPriority; // ← era TaskPriority (si existe), si no usa TaskPriority
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

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
