<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('login', [AuthController::class, 'login'])
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('me',      [AuthController::class, 'me'])
            ->name('auth.me');

        Route::post('logout', [AuthController::class, 'logout'])
            ->name('auth.logout');

        Route::post('refresh-permissions', [AuthController::class, 'refreshPermissions'])
            ->name('auth.refresh-permissions');

        Route::post('register', [AuthController::class, 'register'])
            ->middleware('role:super-admin')
            ->name('auth.register');
    });
});
