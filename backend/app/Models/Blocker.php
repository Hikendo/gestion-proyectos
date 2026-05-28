<?php

// app/Models/Blocker.php

namespace App\Models;

use App\Enums\BlockerSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Blocker extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'task_id',
        'created_by',
        'title',
        'description',
        'severity',
        'resolved',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'severity' => BlockerSeverity::class,
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

