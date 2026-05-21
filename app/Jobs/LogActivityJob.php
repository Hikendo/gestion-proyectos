<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ?int $userId,
        private readonly string $module,
        private readonly string $action,
        private readonly array $data = [],
        private readonly ?string $ipAddress = null
    ) {}

    public function handle(): void
    {
        ActivityLog::create([
            'user_id'    => $this->userId,
            'module'     => $this->module,
            'action'     => $this->action,
            'data'       => $this->data,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
