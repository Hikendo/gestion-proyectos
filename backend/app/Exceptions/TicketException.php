<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class TicketException extends DomainException
{
    public static function notFound(int $id): static
    {
        return new static("Ticket #{$id} no encontrado.", Response::HTTP_NOT_FOUND);
    }

    public static function alreadyClosed(): static
    {
        return new static('El ticket ya está cerrado.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function doesNotBelongToProject(): static
    {
        return new static('El ticket no pertenece al proyecto indicado.', Response::HTTP_NOT_FOUND);
    }
}
