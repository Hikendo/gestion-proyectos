<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param User $user El usuario cuyo rol fue modificado.
     * @param string|null $oldRole Rol anterior (puede ser null si no tenía).
     * @param string|null $newRole Rol nuevo (puede ser null si se removió el rol).
     */
    public function __construct(
        public readonly User $user,
        public readonly ?string $oldRole,
        public readonly ?string $newRole,
    ) {}
}
