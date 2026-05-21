<?php

use App\Http\Controllers\Api\RiskController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('risks', [RiskController::class, 'index'])
        ->name('projects.risks.index');

    Route::post('risks', [RiskController::class, 'store'])
        ->name('projects.risks.store');

    Route::put('risks/{risk}', [RiskController::class, 'update'])
        ->name('projects.risks.update');

    Route::delete('risks/{risk}', [RiskController::class, 'destroy'])
        ->name('projects.risks.destroy');
});
