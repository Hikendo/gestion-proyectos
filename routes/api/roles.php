<?php

use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:super-admin|project-manager'])->group(function () {

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
});
