<?php
// app/Models/UserMetric.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assigned_tasks',
        'completed_tasks',
        'worked_minutes',
        'performance_score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
