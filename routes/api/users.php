<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('users/all',        [UserController::class, 'all'])->name('users.all');
    Route::get('users',          [UserController::class, 'index'])->name('users.index');
    Route::post('users',         [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}',   [UserController::class, 'show'])->name('users.show');
    Route::put('users/{user}',   [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('users/{user}/metrics', [UserController::class, 'metrics'])
        ->name('users.metrics');
});
