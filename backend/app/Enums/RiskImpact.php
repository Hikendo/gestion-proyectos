<?php

namespace App\Enums;

enum RiskImpact: string
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low      => 'Bajo',
            self::Medium   => 'Medio',
            self::High     => 'Alto',
            self::Critical => 'Crítico',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low      => 1,
            self::Medium   => 2,
            self::High     => 3,
            self::Critical => 4,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
