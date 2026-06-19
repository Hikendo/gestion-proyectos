<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Routes
|--------------------------------------------------------------------------
|
| Here you can register the API routes needed for broadcasting auth.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "api" middleware group.
|
*/

Broadcast::routes(['middleware' => ['auth:sanctum']]);