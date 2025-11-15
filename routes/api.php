<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\v1\AccountController;
use App\Http\Controllers\API\v1\TradeController;
use App\Http\Controllers\API\v1\MarketRateController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{id}', [AccountController::class, 'show']);

    Route::get('/market/rate', [MarketRateController::class, 'getRate']);

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/trades/execute', [TradeController::class, 'execute']);
        Route::post('/trades/account-to-account', [TradeController::class, 'accountToAccount']);
    });

    Route::get('/trades', [TradeController::class, 'index']);
    Route::get('/trades/{id}', [TradeController::class, 'show']);
});
