<?php

namespace App\Traits;

use App\Jobs\LogActivityJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasActivityLog
{
    /**
     * Registra una acción en el log de auditoría de forma async.
     *
     * Uso: $this->logActivity('task', 'status_changed', ['from' => ..., 'to' => ...]);
     */
    public function logActivity(string $module, string $action, array $data = []): void
    {
        LogActivityJob::dispatch(
            userId: Auth::id(),
            module: $module,
            action: $action,
            data: $data,
            ipAddress: Request::ip()
        );
    }

    /**
     * Genera el array de data estándar para el log basado en el modelo actual.
     * Merge con datos adicionales opcionales.
     */
    public function buildLogData(array $extra = []): array
    {
        return array_merge([
            'model'   => class_basename($this),
            'id'      => $this->id ?? null,
        ], $extra);
    }
}
