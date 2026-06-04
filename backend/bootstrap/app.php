<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\DomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            // 💡 SI la petición va hacia Horizon, deja que Laravel/Horizon la manejen normalmente 
            // para que la ventana emergente HTTP Básica o el login web funcionen.
            if ($request->is('horizon*')) {
                return null; // Al retornar null, Laravel ignora este renderizador y usa el comportamiento por defecto
            }

            // Para todo lo demás (tu API), sigue respondiendo el JSON de siempre
            return response()->json(['message' => 'No autenticado.'], 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json(['message' => 'Recurso no encontrado.'], 404);
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
        });
    })->create();
