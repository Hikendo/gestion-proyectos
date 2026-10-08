<?php

use App\Http\Controllers\Api\TicketCommentController;
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

    Route::post('tickets/{ticket}/attachments', [TicketController::class, 'uploadAttachments'])
        ->name('projects.tickets.attachments.upload');

    // Seguimiento / comentarios del ticket
    Route::get('tickets/{ticket}/comments', [TicketCommentController::class, 'index'])
        ->name('projects.tickets.comments.index');

    Route::post('tickets/{ticket}/comments', [TicketCommentController::class, 'store'])
        ->name('projects.tickets.comments.store');

    Route::delete('tickets/{ticket}/comments/{comment}', [TicketCommentController::class, 'destroy'])
        ->name('projects.tickets.comments.destroy');
});

// Sub-recurso sin prefijo de proyecto: permite subir adjuntos con la URL
// que usa el frontend (/{parentType}/{parentId}/attachments), igual que tasks.
Route::middleware('auth:sanctum')->group(function () {

    Route::post('tickets/{ticket}/attachments', [TicketController::class, 'uploadTicketAttachments'])
        ->name('tickets.attachments.upload');
});
