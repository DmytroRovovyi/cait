<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelegramController;

Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
Route::apiResource('tasks', TaskController::class);
