<?php

// app/Models/Risk.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Risk extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'impact',
        'probability',
        'mitigation_plan',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
