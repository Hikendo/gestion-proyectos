<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DirectChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Group chat (project-scoped)
    Route::prefix('projects/{project}')->group(function () {
        Route::get('chat/messages', [ChatController::class, 'index']);
        Route::post('chat/messages', [ChatController::class, 'store']);

        // Private conversations (project-scoped)
        Route::get('conversations', [DirectChatController::class, 'index']);
        Route::post('conversations', [DirectChatController::class, 'store']);
    });

    // Private messages (conversation-scoped)
    Route::prefix('conversations/{conversation}')->group(function () {
        Route::get('messages', [DirectChatController::class, 'messages']);
        Route::post('messages', [DirectChatController::class, 'sendMessage']);
        Route::post('read', [DirectChatController::class, 'markRead']);
    });

});
