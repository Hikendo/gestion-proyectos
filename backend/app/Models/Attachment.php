<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Attachment extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'attachable_type',
        'attachable_id',
        'original_name',
        'disk_path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Atributos que se incluirán en la serialización JSON.
     *
     * @var array<int, string>
     */
    protected $appends = ['download_url'];

    protected static function booted(): void
    {
        static::creating(function (Attachment $attachment) {
            if (empty($attachment->uuid)) {
                $attachment->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relación polimórfica: Task, Ticket, Blocker, Project, etc.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usuario que subió el archivo.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * URL pública de descarga apuntando al endpoint seguro.
     */
    public function getDownloadUrlAttribute(): string
    {
        return config('app.url') . '/api/v1/attachments/download/' . $this->uuid;
    }
}
