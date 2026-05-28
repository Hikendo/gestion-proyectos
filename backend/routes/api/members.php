<?php

use App\Http\Controllers\Api\ProjectMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('members', [ProjectMemberController::class, 'index'])
        ->name('projects.members.index');

    Route::post('members', [ProjectMemberController::class, 'store'])
        ->name('projects.members.store');

    Route::delete('members/{user}', [ProjectMemberController::class, 'destroy'])
        ->name('projects.members.destroy');
    Route::get('members/{member}', [ProjectMemberController::class, 'show'])
        ->name('projects.members.show');
});
