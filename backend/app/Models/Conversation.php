<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'project_id',
        'user_one_id',
        'user_two_id',
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

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DirectMessage::class)->orderBy('created_at');
    }

    /**
     * ----------------------------------------------------------------
     * HELPERS
     * ----------------------------------------------------------------
     */

    /**
     * Get the other participant given one user.
     */
    public function otherUser(User $user): User
    {
        return $this->user_one_id === $user->id
            ? $this->userTwo
            : $this->userOne;
    }

    /**
     * Check if a user is a participant.
     */
    public function hasParticipant(User $user): bool
    {
        return $this->user_one_id === $user->id
            || $this->user_two_id === $user->id;
    }

    /**
     * Count unread messages for a given user.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}