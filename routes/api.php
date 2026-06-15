<?php

use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductApiController::class, 'index']);

Route::get('/orders', [OrderApiController::class, 'index']);
Route::get('/orders/{order}', [OrderApiController::class, 'show']);
Route::post('/orders', [OrderApiController::class, 'store']);
Route::patch('/orders/{order}/status', [OrderApiController::class, 'updateStatus']);
