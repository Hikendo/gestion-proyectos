<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class AuthException extends DomainException
{
    public static function invalidCredentials(): static
    {
        return new static('Credenciales incorrectas.', Response::HTTP_UNAUTHORIZED);
    }

    public static function unauthenticated(): static
    {
        return new static('No autenticado.', Response::HTTP_UNAUTHORIZED);
    }

    public static function forbidden(): static
    {
        return new static('No tienes permiso para realizar esta acción.', Response::HTTP_FORBIDDEN);
    }
}
