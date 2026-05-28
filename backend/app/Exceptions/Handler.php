<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        DomainException::class,
    ];

    public function register(): void
    {
        // Errores de dominio propios
        $this->renderable(function (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        });

        // 401 — no autenticado
        $this->renderable(function (AuthenticationException $e) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        });

        // 403 — sin permiso
        $this->renderable(function (AccessDeniedHttpException $e) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        });

        // 404 — modelo no encontrado via route model binding
        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'Recurso no encontrado.',
            ], 404);
        });

        // 422 — validación
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
        });
    }
}
