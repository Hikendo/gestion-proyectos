<?php

namespace App\Enums;

enum PhaseStatus: string
{
    case Planned    = 'planned';
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Expired    = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Planned    => 'Planificada',
            self::InProgress => 'En Progreso',
            self::Completed  => 'Completada',
            self::Expired    => 'Vencida',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    public function isExpired(): bool
    {
        return $this === self::Expired;
    }

    /**
     * Transiciones válidas por estado actual.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Planned    => [self::InProgress],
            self::InProgress => [self::Completed, self::Expired],
            self::Expired    => [self::InProgress],
            self::Completed  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
