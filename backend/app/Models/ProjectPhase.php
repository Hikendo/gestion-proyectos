<?php

// app/Models/ProjectPhase.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Enums\PhaseStatus;

class ProjectPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'start_date',
        'end_date',
        'progress',
        'status',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'status'       => PhaseStatus::class,
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'phase_id');
    }

    public function acceptanceCriteria()
    {
        return $this->hasMany(AcceptanceCriterion::class, 'phase_id');
    }

    public function objectives()
    {
        return $this->hasMany(Objective::class, 'phase_id');
    }

    public function risks()
    {
        return $this->hasMany(Risk::class, 'phase_id');
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class, 'phase_id');
    }

    /**
     * Calcula las horas totales estimadas de la fase.
     */
    public function totalEstimatedHours(): int
    {
        return (int) $this->tasks()->sum('estimated_hours');
    }
}
