<?php

use App\Http\Controllers\Api\Admin\PromoController;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\Seller\StoreController as SellerStoreController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Driver\DeliveryController as DriverDeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/switch-role', [AuthController::class, 'switchRole']);
    });
});

// Public endpoints
Route::get('/stores', [StoreController::class, 'index']);
Route::get('/stores/{id}', [StoreController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/reviews', [ReviewController::class, 'index']);

// Seller endpoints
Route::middleware(['auth:sanctum', 'role:seller'])->prefix('seller')->group(function () {
    Route::get('/store', [SellerStoreController::class, 'show']);
    Route::post('/store', [SellerStoreController::class, 'store']);
    Route::put('/store', [SellerStoreController::class, 'update']);

    Route::get('/products', [SellerProductController::class, 'index']);
    Route::get('/products/{id}', [SellerProductController::class, 'show']);
    Route::apiResource('products', SellerProductController::class)->except(['index', 'show']);

    // Seller Order Management
    Route::get('/orders', [SellerOrderController::class, 'index']);
    Route::get('/orders/{id}', [SellerOrderController::class, 'show']);
    Route::put('/orders/{id}/process', [SellerOrderController::class, 'process']);
});

Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'addItem']);
    Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
});

// Wallet endpoints
Route::middleware('auth:sanctum')->prefix('wallet')->group(function () {
    Route::get('/', [WalletController::class, 'show']);
    Route::get('/transactions', [WalletController::class, 'transactions']);
    Route::post('/topup', [WalletController::class, 'topup']);
    Route::get('/topup/status/{merchantOrderId}', [WalletController::class, 'checkStatus']);
});

// Callback DuitKu - PUBLIC (tanpa auth:sanctum)
Route::post('/wallet/topup/callback', [WalletController::class, 'callback']);

// Checkout endpoint
Route::middleware('auth:sanctum')->post('/checkout', [CheckoutController::class, 'checkout']);

// Admin - Voucher & Promo Management (protected, auth:sanctum + role:admin)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('vouchers', VoucherController::class);
    Route::apiResource('promos', PromoController::class);
});

// Driver endpoints
Route::middleware(['auth:sanctum', 'role:driver'])->prefix('driver')->group(function () {
    Route::get('/jobs', [DriverDeliveryController::class, 'availableJobs']);
    Route::get('/jobs/history', [DriverDeliveryController::class, 'history']);
    Route::get('/jobs/active', [DriverDeliveryController::class, 'active']);
    Route::post('/jobs/{deliveryId}/take', [DriverDeliveryController::class, 'take']);
    Route::put('/jobs/{deliveryId}/complete', [DriverDeliveryController::class, 'complete']);
});
