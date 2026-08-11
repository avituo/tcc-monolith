<?php

use App\Http\Controllers\ExperimentAuthenticationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UpdateOrderStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products')->name('home');

if (config('experiment.session_authentication')) {
    Route::get('/experiment/auth/csrf', ExperimentAuthenticationController::class)
        ->name('experiment.auth.csrf');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('products', ProductController::class);
    Route::patch('orders/{order}/status', UpdateOrderStatusController::class)
        ->name('orders.status.update');
    Route::resource('orders', OrderController::class);
});

require __DIR__.'/settings.php';
