<?php

use App\Http\Controllers\Api\MilestoneController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('milestones', [MilestoneController::class, 'index'])
        ->name('projects.milestones.index');

    Route::post('milestones', [MilestoneController::class, 'store'])
        ->name('projects.milestones.store');

    Route::put('milestones/{milestone}', [MilestoneController::class, 'update'])
        ->name('projects.milestones.update');

    Route::delete('milestones/{milestone}', [MilestoneController::class, 'destroy'])
        ->name('projects.milestones.destroy');
    Route::get('milestones/{milestone}', [MilestoneController::class, 'show'])
        ->name('projects.milestones.show');
});
