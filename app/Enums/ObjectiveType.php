<?php

namespace App\Enums;

enum ObjectiveType: string
{
    case General  = 'general';
    case Specific = 'specific';

    public function label(): string
    {
        return match ($this) {
            self::General  => 'General',
            self::Specific => 'Específico',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
