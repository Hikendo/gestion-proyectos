<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BlockerException extends DomainException
{
    public static function notFound(int $id): static
    {
        return new static("Blocker #{$id} no encontrado.", Response::HTTP_NOT_FOUND);
    }

    public static function alreadyResolved(): static
    {
        return new static('El blocker ya fue resuelto.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function doesNotBelongToProject(): static
    {
        return new static('El blocker no pertenece al proyecto indicado.', Response::HTTP_NOT_FOUND);
    }
}
