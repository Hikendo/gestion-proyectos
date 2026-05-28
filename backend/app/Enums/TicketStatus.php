<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Abierto',
            self::InProgress => 'En Progreso',
            self::Resolved   => 'Resuelto',
            self::Closed     => 'Cerrado',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
