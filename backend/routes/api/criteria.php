<?php

use App\Http\Controllers\Api\AcceptanceCriterionController;
use Illuminate\Support\Facades\Route;

Route::controller(AcceptanceCriterionController::class)->group(function () {
    Route::get('/projects/{project}/phases/{phase}/criteria', 'index');
    Route::post('/projects/{project}/phases/{phase}/criteria', 'store');
    Route::get('/projects/{project}/phases/{phase}/criteria/{criterion}', 'show');
    Route::put('/projects/{project}/phases/{phase}/criteria/{criterion}', 'update');
    Route::delete('/projects/{project}/phases/{phase}/criteria/{criterion}', 'destroy');
});
