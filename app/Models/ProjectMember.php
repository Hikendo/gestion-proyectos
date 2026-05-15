<?php

// app/Models/ProjectMember.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectMember extends Model
{
    use HasFactory;

    protected $table = 'project_members';

    protected $fillable = [
        'project_id',
        'user_id',
        'role',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
