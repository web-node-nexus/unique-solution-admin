<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AppCatalogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CustomerOrderController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\StockAlertController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public App APIs — Unique Solution customer mobile/web
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::get('/shop', [AppCatalogController::class, 'shop']);
    Route::get('/home', [AppCatalogController::class, 'home']);
    Route::get('/banners', [AppCatalogController::class, 'banners']);
    Route::get('/categories', [AppCatalogController::class, 'categories']);
    Route::get('/category-sales', [AppCatalogController::class, 'categorySales']);
    Route::get('/sales', [AppCatalogController::class, 'sales']);
    Route::get('/coupons', [AppCatalogController::class, 'coupons']);
    Route::post('/coupons/preview', [AppCatalogController::class, 'previewCoupon']);
    Route::get('/notifications', [AppCatalogController::class, 'notifications']);
    Route::get('/announcements', [AppCatalogController::class, 'notifications']);
    Route::get('/brands', [AppCatalogController::class, 'brands']);
    Route::get('/products', [AppCatalogController::class, 'products']);
    Route::get('/products/filters', [AppCatalogController::class, 'productFilters']);
    Route::get('/products/suggest', [AppCatalogController::class, 'searchSuggest']);
    Route::get('/products/{product}', [AppCatalogController::class, 'productShow']);
    Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);
    Route::post('/delivery/check', [AppCatalogController::class, 'checkPincode']);

    Route::get('/orders/{order}/invoice-download', [CustomerOrderController::class, 'downloadInvoice'])
        ->middleware('signed')
        ->name('api.v1.orders.invoice.download');

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    Route::post('/analytics/events', [TelemetryController::class, 'analytics']);
    Route::post('/crashes', [TelemetryController::class, 'crash']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/password', [AuthController::class, 'changePassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', [CustomerOrderController::class, 'dashboard']);
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);
        Route::post('/orders/{order}/reorder', [CustomerOrderController::class, 'reorder']);
        Route::post('/orders/{order}/verify-payment', [CustomerOrderController::class, 'verifyPayment']);
        Route::post('/orders/{order}/return', [CustomerOrderController::class, 'requestReturn']);
        Route::get('/orders/{order}/invoice', [CustomerOrderController::class, 'invoice']);
        Route::post('/checkout', [CustomerOrderController::class, 'checkout']);

        Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
        Route::post('/stock-alerts', [StockAlertController::class, 'store']);

        Route::get('/addresses', [AddressController::class, 'index']);
        Route::post('/addresses', [AddressController::class, 'store']);
        Route::put('/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::post('/wishlist/sync', [WishlistController::class, 'sync']);
        Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);

        Route::get('/cart', [CartController::class, 'index']);
        Route::put('/cart', [CartController::class, 'sync']);
        Route::delete('/cart', [CartController::class, 'clear']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
