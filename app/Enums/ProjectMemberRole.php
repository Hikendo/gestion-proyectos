<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case Manager   = 'manager';
    case Developer = 'developer';
    case Qa        = 'qa';
    case Support   = 'support';
    case Client    = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Manager   => 'Project Manager',
            self::Developer => 'Developer',
            self::Qa        => 'QA',
            self::Support   => 'Support',
            self::Client    => 'Cliente',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
