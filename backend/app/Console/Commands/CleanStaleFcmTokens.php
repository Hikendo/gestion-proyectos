<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FcmToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Elimina tokens FCM que no han sido utilizados en un período de días
 * configurable, reduciendo la tabla y evitando envíos a destinos obsoletos.
 */
class CleanStaleFcmTokens extends Command
{
    protected $signature = 'fcm:clean-stale-tokens {--days=90 : Días de inactividad para considerar un token como obsoleto} {--dry-run : Solo muestra cuántos tokens serían eliminados sin borrarlos realmente}';

    protected $description = 'Elimina tokens FCM que no se han usado en N días';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $query = FcmToken::where('last_used_at', '<', $cutoff)
            ->orWhereNull('last_used_at');

        if ($this->option('dry-run')) {
            $count = $query->count();
            $this->info("Modo dry-run: {$count} token(s) stale serían eliminados (inactivos por más de {$days} días).");

            return self::SUCCESS;
        }

        $count = $query->delete();

        Log::channel('notifications')->info(
            "Limpieza de tokens FCM: {$count} token(s) stale eliminados.",
            ['days_threshold' => $days]
        );

        $this->info("{$count} token(s) stale eliminados correctamente.");

        return self::SUCCESS;
    }
}
