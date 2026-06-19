<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMessage extends Model
{
    use HasFactory;

    protected $table = 'project_messages';

    protected $fillable = [
        'project_id',
        'user_id',
        'content',
    ];

    /**
     * ----------------------------------------------------------------
     * RELATIONS
     * ----------------------------------------------------------------
     */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}