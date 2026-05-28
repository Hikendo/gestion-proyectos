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
        'name',
        'description',
        'delivery_date',
        'approved',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'approved' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
