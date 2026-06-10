<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;


Route::get('/orders', [OrderController::class, 'getList']);
Route::get('/products', [ProductController::class, 'getList']);

