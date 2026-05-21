<?php

use App\Http\Controllers\Api\ObjectiveController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('objectives', [ObjectiveController::class, 'index'])
        ->name('projects.objectives.index');

    Route::post('objectives', [ObjectiveController::class, 'store'])
        ->name('projects.objectives.store');

    Route::put('objectives/{objective}', [ObjectiveController::class, 'update'])
        ->name('projects.objectives.update');
});
