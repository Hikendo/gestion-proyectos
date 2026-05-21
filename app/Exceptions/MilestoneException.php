<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class MilestoneException extends DomainException
{
    public static function alreadyCompleted(): static
    {
        return new static('El milestone ya está completado.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function doesNotBelongToProject(): static
    {
        return new static('El milestone no pertenece al proyecto indicado.', Response::HTTP_NOT_FOUND);
    }
}
