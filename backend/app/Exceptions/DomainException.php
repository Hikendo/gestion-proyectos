<?php

namespace App\Exceptions;

use RuntimeException;

abstract class DomainException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 422
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
