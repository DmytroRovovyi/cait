<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TelegramController;

Route::post('/telegram/webhook', [TelegramController::class, 'handle']);
