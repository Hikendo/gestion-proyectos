<?php

namespace App\Enums;

enum RiskStatus: string
{
    case Active    = 'active';
    case Mitigated = 'mitigated';
    case Resolved  = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Activo',
            self::Mitigated => 'Mitigado',
            self::Resolved  => 'Resuelto',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
