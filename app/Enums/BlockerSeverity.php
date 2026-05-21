<?php

namespace App\Enums;

enum BlockerSeverity: string
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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
