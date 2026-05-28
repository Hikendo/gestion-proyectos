<?php

use App\Http\Controllers\Api\DeliverableController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('deliverables', [DeliverableController::class, 'index'])
        ->name('projects.deliverables.index');

    Route::post('deliverables', [DeliverableController::class, 'store'])
        ->name('projects.deliverables.store');
    Route::get('deliverables/{deliverable}', [DeliverableController::class, 'show'])
        ->name('projects.deliverables.show');

    Route::put('deliverables/{deliverable}', [DeliverableController::class, 'update'])
        ->name('projects.deliverables.update');

    Route::patch('deliverables/{deliverable}/approve', [DeliverableController::class, 'approve'])
        ->name('projects.deliverables.approve');
});
