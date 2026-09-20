<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CourierWebhookController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SSLCommerzController;
use App\Http\Controllers\Api\V1\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Customer Authentication (Public)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Categories
    Route::get('/categories/tree', [CategoryController::class, 'tree']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // Cart
    Route::post('/cart/validate-coupon', [CartController::class, 'validateCoupon']);

    // Checkout (Supports both Guest and Authenticated users)
    Route::post('/checkout/order', [CheckoutController::class, 'order']);
    Route::get('/checkout/order/{orderNumber}', [CheckoutController::class, 'show']);

    // SSLCommerz Payment Callbacks & Webhooks (Public endpoints for gateway redirects & IPN)
    Route::prefix('payment/sslcommerz')->group(function () {
        Route::post('/initiate/{orderNumber}', [SSLCommerzController::class, 'initiate']);
        Route::post('/success', [SSLCommerzController::class, 'success'])->name('api.payment.sslcommerz.success');
        Route::post('/fail', [SSLCommerzController::class, 'fail'])->name('api.payment.sslcommerz.fail');
        Route::post('/cancel', [SSLCommerzController::class, 'cancel'])->name('api.payment.sslcommerz.cancel');
        Route::post('/ipn', [SSLCommerzController::class, 'ipn'])->name('api.payment.sslcommerz.ipn');
        Route::get('/sandbox-checkout', [SSLCommerzController::class, 'sandboxCheckout'])->name('api.payment.sslcommerz.sandbox');
    });

    // Courier Webhooks (External delivery status notifications)
    Route::post('/webhooks/courier/{provider}', [CourierWebhookController::class, 'handle'])->name('api.courier.webhook');

    // Authenticated Customer Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Profile
        Route::get('/user/profile', [UserProfileController::class, 'show']);
        Route::put('/user/profile', [UserProfileController::class, 'update']);

        // Customer Order History (Strictly scoped to authenticated user)
        Route::get('/customer/orders', [CustomerOrderController::class, 'index']);
        Route::get('/user/orders', [UserProfileController::class, 'orders']);
        Route::get('/user/orders/{orderNumber}', [UserProfileController::class, 'showOrder']);

        // Saved Addresses
        Route::post('/user/addresses', [UserProfileController::class, 'storeAddress']);
        Route::put('/user/addresses/{id}', [UserProfileController::class, 'updateAddress']);
        Route::delete('/user/addresses/{id}', [UserProfileController::class, 'deleteAddress']);
        Route::post('/user/addresses/{id}/default', [UserProfileController::class, 'setDefaultAddress']);
    });
});

