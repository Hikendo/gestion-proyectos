<?php

// app/Models/Project.php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Project extends Model
{
    use HasFactory, SoftDeletes, Searchable, HasAttachments;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget',
        'progress',
        'owner_id',
        'uuid',
    ];

    public function toSearchableArray(): array
    {
        return [
            'name'        => $this->name,
            'code'        => $this->code,
            'description' => $this->description,
        ];
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'budget'     => 'decimal:2',
        'status'     => ProjectStatus::class,
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function phases()
    {
        return $this->hasMany(ProjectPhase::class);
    }

    public function objectives()
    {
        return $this->hasMany(Objective::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function risks()
    {
        return $this->hasMany(Risk::class);
    }

    public function blockers()
    {
        return $this->hasMany(Blocker::class);
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class);
    }

    public function plans()
    {
        return $this->hasMany(ProjectPlan::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    public function metrics()
    {
        return $this->hasOne(ProjectMetric::class);
    }

    /**
     * ----------------------------------------------------------------
     * CHAT RELATIONS
     * ----------------------------------------------------------------
     */

    public function groupMessages()
    {
        return $this->hasMany(ProjectMessage::class)->orderBy('created_at');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
