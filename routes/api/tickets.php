<?php

use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('projects/{project}')->group(function () {

    Route::get('tickets', [TicketController::class, 'index'])
        ->name('projects.tickets.index');

    Route::post('tickets', [TicketController::class, 'store'])
        ->name('projects.tickets.store');

    Route::get('tickets/{ticket}', [TicketController::class, 'show'])
        ->name('projects.tickets.show');

    Route::put('tickets/{ticket}', [TicketController::class, 'update'])
        ->name('projects.tickets.update');

    Route::delete('tickets/{ticket}', [TicketController::class, 'destroy'])
        ->name('projects.tickets.destroy');
});
