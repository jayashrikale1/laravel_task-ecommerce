<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/cart/items', [CartController::class, 'store']);
    Route::get('/cart', [CartController::class, 'show']);
    Route::patch('/cart/items/{product}', [CartController::class, 'update']);
    Route::delete('/cart/items/{product}', [CartController::class, 'destroy']);
    Route::post('/cart/checkout', [CartController::class, 'checkout']);
});

