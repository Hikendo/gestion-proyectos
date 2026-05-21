<?php

namespace App\Enums;

enum RiskProbability: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Baja',
            self::Medium => 'Media',
            self::High   => 'Alta',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low    => 1,
            self::Medium => 2,
            self::High   => 3,
        };
    }

    /**
     * Criticidad = impacto * probabilidad.
     * Usado en RiskService para calcular nivel de alerta.
     */
    public function criticality(RiskImpact $impact): int
    {
        return $this->weight() * $impact->weight();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
