<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;

Route::get('/', function () {
    return view('welcome');
});

// Registrar explícitamente el nombre 'login' para interceptar redirecciones automáticas
Route::get('/login-api', function (): JsonResponse {
    return response()->json([
        'status' => false,
        'message' => 'Unauthenticated. Please log in through the frontend app.'
    ], 401);
})->name('login'); // 👈 El nombre mágico que Laravel Horizon está buscando