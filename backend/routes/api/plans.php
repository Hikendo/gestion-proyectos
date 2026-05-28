<?php

use App\Http\Controllers\Api\ProjectPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('plan', [ProjectPlanController::class, 'show'])
        ->name('projects.plan.show');

    Route::post('plan', [ProjectPlanController::class, 'store'])
        ->name('projects.plan.store');
});
