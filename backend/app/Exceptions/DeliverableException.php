<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class DeliverableException extends DomainException
{
    public static function alreadyApproved(): self
    {
        return new self('El entregable ya fue aprobado.', 422); // ← debe ser 422
    }

    public static function doesNotBelongToProject(): static
    {
        return new static('El entregable no pertenece al proyecto indicado.', Response::HTTP_NOT_FOUND);
    }
}
