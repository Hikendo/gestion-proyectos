<?php

declare(strict_types=1);

use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

// Cambia 'auth:sanctum' por el driver exacto que uses en auth.php si fuera necesario
Route::middleware('auth:sanctum')->group(function () {

    // Gestión de Tokens FCM multidispositivo
    Route::prefix('fcm')->group(function () {
        Route::get('tokens', [FcmTokenController::class, 'index']);
        Route::post('register-token', [FcmTokenController::class, 'register']);
        Route::post('remove-token', [FcmTokenController::class, 'remove']);
    });

    // Historial y Acciones de Notificaciones
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('{id}', [NotificationController::class, 'show']);
        Route::post('mark-read', [NotificationController::class, 'markRead']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead']);

        // Despachos y Pruebas asíncronas
        Route::post('schedule', [NotificationController::class, 'schedule']);
    });
});
