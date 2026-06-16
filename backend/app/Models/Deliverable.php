<?php

// app/Models/Deliverable.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Deliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase_id',
        'parent_id',
        'name',
        'description',
        'delivery_date',
        'approved',
        'approved_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'approved' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function phase()
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function parent()
    {
        return $this->belongsTo(Deliverable::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Deliverable::class, 'parent_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
