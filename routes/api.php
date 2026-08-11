<?php

use App\Http\Controllers\Api\V1\CancelOrderController;
use App\Http\Controllers\Api\V1\Internal\MarkOrderPaidController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

$authenticatedApiMiddleware = config('experiment.session_authentication')
    ? ['web', 'auth', 'throttle:api']
    : ['auth', 'throttle:api'];

Route::prefix('v1')->as('api.v1.')->middleware($authenticatedApiMiddleware)->group(function (): void {
    Route::apiResource('products', ProductController::class)->names('products');
    Route::apiResource('orders', OrderController::class)->names('orders');
    Route::post('orders/{order}/cancel', CancelOrderController::class)->name('orders.cancel');
});

Route::prefix('v1/internal')->as('api.v1.internal.')->middleware($authenticatedApiMiddleware)->group(function (): void {
    Route::post('orders/{order}/paid', MarkOrderPaidController::class)->name('orders.paid');
});
