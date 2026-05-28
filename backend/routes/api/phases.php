<?php

use App\Http\Controllers\Api\ProjectPhaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('phases', [ProjectPhaseController::class, 'index'])
        ->name('projects.phases.index');

    Route::post('phases', [ProjectPhaseController::class, 'store'])
        ->name('projects.phases.store');

    Route::put('phases/{phase}', [ProjectPhaseController::class, 'update'])
        ->name('projects.phases.update');

    Route::delete('phases/{phase}', [ProjectPhaseController::class, 'destroy'])
        ->name('projects.phases.destroy');
    Route::get('phases/{phase}', [ProjectPhaseController::class, 'show'])
        ->name('projects.phases.show');
});
