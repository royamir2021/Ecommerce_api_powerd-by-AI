<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\AIController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware(['jwt.auth'])->group(function () {
    // Product Routes
    Route::apiResource('products', ProductController::class);

    // Cart Routes
    Route::apiResource('cart', CartController::class);

    // Orders
    Route::post('orders', [OrderController::class, 'placeOrder']);
    Route::get('orders', [OrderController::class, 'getUserOrders']);
    Route::get('orders/{id}', [OrderController::class, 'getOrderDetails']);

    // Payments
    Route::post('payments', [PaymentController::class, 'processPayment']);
    Route::get('payments/{id}', [PaymentController::class, 'getPaymentDetails']);

    // Order History
    Route::get('orders/history', [OrderHistoryController::class, 'index']);
    Route::get('orders/history/{id}', [OrderHistoryController::class, 'show']);
    Route::put('orders/history/{id}/status', [OrderHistoryController::class, 'updateStatus']);
    Route::post('products/{id}/generate-description', [AIController::class, 'generateProductDescription'])->middleware('jwt.auth');

});
