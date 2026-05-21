<?php

use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskTimeLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Tareas del proyecto
    Route::prefix('projects/{project}')->group(function () {

        Route::get('tasks', [TaskController::class, 'index'])
            ->name('projects.tasks.index');

        Route::post('tasks', [TaskController::class, 'store'])
            ->name('projects.tasks.store');

        Route::get('tasks/{task}', [TaskController::class, 'show'])
            ->name('projects.tasks.show');

        Route::put('tasks/{task}', [TaskController::class, 'update'])
            ->name('projects.tasks.update');

        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
            ->name('projects.tasks.destroy');
    });

    // Sub-recursos de tarea
    Route::prefix('tasks/{task}')->group(function () {

        Route::get('comments', [TaskCommentController::class, 'index'])
            ->name('tasks.comments.index');

        Route::post('comments', [TaskCommentController::class, 'store'])
            ->name('tasks.comments.store');

        Route::delete('comments/{comment}', [TaskCommentController::class, 'destroy'])
            ->name('tasks.comments.destroy');

        Route::get('time-logs', [TaskTimeLogController::class, 'index'])
            ->name('tasks.timelogs.index');

        Route::post('time-logs', [TaskTimeLogController::class, 'store'])
            ->name('tasks.timelogs.store');
    });
});
