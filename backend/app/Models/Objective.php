<?php

// app/Models/Objective.php

namespace App\Models;

use App\Enums\ObjectiveType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Objective extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'title',
        'description',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'type'      => ObjectiveType::class,
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
