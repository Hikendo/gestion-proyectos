<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning   = 'planning';
    case Active     = 'active';
    case OnHold     = 'on_hold';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning  => 'Planificación',
            self::Active    => 'Activo',
            self::OnHold    => 'En Pausa',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
