<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectMessage extends Model
{
    use HasFactory;

    protected $table = 'direct_messages';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * ----------------------------------------------------------------
     * RELATIONS
     * ----------------------------------------------------------------
     */

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ----------------------------------------------------------------
     * SCOPES
     * ----------------------------------------------------------------
     */

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}