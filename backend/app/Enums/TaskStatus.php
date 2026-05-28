<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Review     = 'review';
    case Done       = 'done';
    case Blocked    = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pendiente',
            self::InProgress => 'En Progreso',
            self::Review     => 'En Revisión',
            self::Done       => 'Completada',
            self::Blocked    => 'Bloqueada',
        };
    }

    public function isDone(): bool
    {
        return $this === self::Done;
    }

    public function isBlocked(): bool
    {
        return $this === self::Blocked;
    }

    /**
     * Transiciones válidas por estado actual.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending    => [self::InProgress, self::Blocked],
            self::InProgress => [self::Review, self::Blocked, self::Done],
            self::Review     => [self::InProgress, self::Done],
            self::Blocked    => [self::InProgress, self::Pending],
            self::Done       => [],
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
