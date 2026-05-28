<?php

use App\Http\Controllers\Api\BlockerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('blockers', [BlockerController::class, 'index'])
        ->name('projects.blockers.index');

    Route::post('blockers', [BlockerController::class, 'store'])
        ->name('projects.blockers.store');

    Route::put('blockers/{blocker}', [BlockerController::class, 'update'])
        ->name('projects.blockers.update');

    Route::patch('blockers/{blocker}/resolve', [BlockerController::class, 'resolve'])
        ->name('projects.blockers.resolve');
    Route::get('blockers/{blocker}', [BlockerController::class, 'show'])
        ->name('projects.blockers.show');
});
