<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'data',
        'status',
        'sent_at',
        'read_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Relación inversa con el Usuario destino de la notificación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
