<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcmToken extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'browser',
        'device_name',
        'last_used_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Relación inversa con el Usuario propietario del token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
