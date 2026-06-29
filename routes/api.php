<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\PromoController;
use App\Http\Controllers\Api\Admin\VoucherController;
use App\Http\Controllers\Api\Admin\TimeSimulationController;
use App\Http\Controllers\Api\Admin\OrderOverdueController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\Seller\StoreController as SellerStoreController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Driver\DeliveryController as DriverDeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Forgot Password - Public endpoints (OTP via email)
Route::prefix('auth')->group(function () {
    // Register & Login
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Forgot Password - Send OTP
    Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp']);

    // Forgot Password - Verify OTP
    Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);

    // Forgot Password - Reset Password
    Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword']);

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
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/reviews', [ReviewController::class, 'index']);

// Protected review endpoint (all authenticated users can create reviews)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});

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
    Route::post('/topup/sync/{merchantOrderId}', [WalletController::class, 'syncTopup']);
});

// Public endpoints - Wallet
Route::get('/payment-methods', [WalletController::class, 'getPaymentMethods']);
Route::get('/wallet/check-status/{merchantOrderId}', [WalletController::class, 'checkDuitkuStatus']);

// Callback DuitKu - PUBLIC (tanpa auth:sanctum)
Route::post('/wallet/topup/callback', [WalletController::class, 'callback']);

// Checkout endpoint
Route::middleware('auth:sanctum')->post('/checkout', [CheckoutController::class, 'checkout']);

// Buyer Orders
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});

// Address endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);
    Route::put('/addresses/{id}/default', [AddressController::class, 'setDefault']);
});

// Admin - Voucher & Promo Management (protected, auth:sanctum + role:admin)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Category Management
    Route::apiResource('categories', AdminCategoryController::class);

    Route::apiResource('vouchers', VoucherController::class);
    Route::apiResource('promos', PromoController::class);

    // Time Simulation
    Route::get('/time-simulation', [TimeSimulationController::class, 'show']);
    Route::post('/time-simulation/advance', [TimeSimulationController::class, 'advance']);
    Route::post('/time-simulation/reset', [TimeSimulationController::class, 'reset']);

    // Overdue Order Check
    Route::post('/orders/check-overdue', [OrderOverdueController::class, 'checkOverdue']);
});

// Driver endpoints
Route::middleware(['auth:sanctum', 'role:driver'])->prefix('driver')->group(function () {
    Route::get('/jobs', [DriverDeliveryController::class, 'availableJobs']);
    Route::get('/jobs/history', [DriverDeliveryController::class, 'history']);
    Route::get('/jobs/active', [DriverDeliveryController::class, 'active']);
    Route::post('/jobs/{deliveryId}/take', [DriverDeliveryController::class, 'take']);
    Route::put('/jobs/{deliveryId}/complete', [DriverDeliveryController::class, 'complete']);
});
