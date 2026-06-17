<?php

use App\Http\Controllers\Api\ProjectMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('members', [ProjectMemberController::class, 'index'])
        ->name('projects.members.index');

    Route::post('members', [ProjectMemberController::class, 'store'])
        ->name('projects.members.store');

    Route::get('members/users', [ProjectMemberController::class, 'users'])
        ->name('projects.members.users');

    Route::put('members/{user}', [ProjectMemberController::class, 'update'])
        ->name('projects.members.update');

    Route::patch('members/{user}/suspend', [ProjectMemberController::class, 'suspend'])
        ->name('projects.members.suspend');

    Route::patch('members/{user}/unsuspend', [ProjectMemberController::class, 'unsuspend'])
        ->name('projects.members.unsuspend');

    Route::delete('members/{user}', [ProjectMemberController::class, 'destroy'])
        ->name('projects.members.destroy');
    Route::get('members/{member}', [ProjectMemberController::class, 'show'])
        ->name('projects.members.show');
});
