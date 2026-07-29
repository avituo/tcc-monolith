<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UpdateOrderStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class);
    Route::patch('orders/{order}/status', UpdateOrderStatusController::class)
        ->name('orders.status.update');
    Route::resource('orders', OrderController::class);
});

require __DIR__.'/settings.php';
