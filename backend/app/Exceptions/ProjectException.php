<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ProjectException extends DomainException
{
    public static function notFound(int $id): static
    {
        return new static("Proyecto #{$id} no encontrado.", Response::HTTP_NOT_FOUND);
    }

    public static function accessDenied(): static
    {
        return new static('No tienes acceso a este proyecto.', Response::HTTP_FORBIDDEN);
    }

    public static function closed(): static
    {
        return new static('El proyecto está cerrado y no admite modificaciones.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function duplicateCode(string $code): static
    {
        return new static("El código de proyecto '{$code}' ya existe.", Response::HTTP_CONFLICT);
    }

    public static function memberAlreadyExists(): static
    {
        return new static('El usuario ya es miembro del proyecto.', Response::HTTP_CONFLICT);
    }

    public static function cannotRemoveOwner(): static
    {
        return new static('No se puede remover al owner del proyecto.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function memberNotFound(): static
    {
        return new static('El usuario no es miembro del proyecto.', Response::HTTP_NOT_FOUND);
    }

    public static function cannotChangeOwnerRole(): static
    {
        return new static('No se puede cambiar el rol del owner del proyecto.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function memberAlreadySuspended(): static
    {
        return new static('El miembro ya está suspendido.', Response::HTTP_CONFLICT);
    }

    public static function memberNotSuspended(): static
    {
        return new static('El miembro no está suspendido.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
