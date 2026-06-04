<?php

use App\Http\Controllers\Api\ProjectReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get(
        'projects/{project}/reports/executive',
        [ProjectReportController::class, 'executive']
    )->name('projects.reports.executive');

    Route::get(
        'projects/{project}/reports/dashboard',
        [ProjectReportController::class, 'dashboard']
    )->name('projects.reports.dashboard');
});
