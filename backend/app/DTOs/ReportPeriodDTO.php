<?php

namespace App\DTOs;

use Carbon\Carbon;

final class ReportPeriodDTO
{
    public readonly ?Carbon $from;
    public readonly ?Carbon $to;
    public readonly string  $label;

    public function __construct(string $period = 'full')
    {
        [$this->from, $this->to, $this->label] = match ($period) {
            'last_month' => [
                Carbon::now()->subMonthNoOverflow()->startOfMonth(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth(),
                'Último Mes (' . Carbon::now()->subMonthNoOverflow()->translatedFormat('F Y') . ')',
            ],
            'last_quarter' => [
                Carbon::now()->subQuarter()->firstOfQuarter()->startOfDay(),
                Carbon::now()->subQuarter()->lastOfQuarter()->endOfDay(),
                'Último Trimestre (Q' . Carbon::now()->subQuarter()->quarter . ' ' . Carbon::now()->subQuarter()->year . ')',
            ],
            default => [null, null, 'Reporte Completo'],
        };
    }

    /** Apply the period filter to a query builder instance. */
    public function apply(mixed $query, string $column = 'created_at'): mixed
    {
        if ($this->from) {
            $query->where($column, '>=', $this->from);
        }
        if ($this->to) {
            $query->where($column, '<=', $this->to);
        }

        return $query;
    }
}
