<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AttachmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('attachments/download/{uuid}', [AttachmentController::class, 'download']);
    Route::post('attachments/upload-temp', [AttachmentController::class, 'uploadTemporary']);
    Route::post('attachments/claim', [AttachmentController::class, 'claim']);
    Route::delete('attachments/{uuid}', [AttachmentController::class, 'destroy']);
    Route::post('attachments/{uuid}/replace', [AttachmentController::class, 'replace']);
});
