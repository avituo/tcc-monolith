<?php

use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\UpdateOrderStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{order}/status', UpdateOrderStatusController::class);
});
