<?php

// app/Models/ProjectPlan.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'scope',
        'requirements',
        'technical_notes',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
