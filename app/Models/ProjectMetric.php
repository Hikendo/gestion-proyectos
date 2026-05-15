<?php

// app/Models/ProjectMetric.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'total_tasks',
        'completed_tasks',
        'open_tickets',
        'total_blockers',
        'completion_rate',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
