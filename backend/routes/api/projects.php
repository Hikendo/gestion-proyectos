<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMetricsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('projects',            [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects',           [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}',  [ProjectController::class, 'show'])->name('projects.show');
    Route::put('projects/{project}',  [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('projects/{project}/metrics', [ProjectMetricsController::class, 'show'])->name('projects.metrics');
});

