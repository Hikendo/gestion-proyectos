<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCommentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly TicketComment $comment,
        public readonly User          $author
    ) {}
}
