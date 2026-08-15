<?php

use App\Http\Controllers\Api\AppCatalogController;
use App\Http\Controllers\Api\DeviceTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public App APIs (no auth) — used by future customer mobile/web app
| Admin changes to category order / banners reflect immediately here.
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
    Route::get('/notifications', [AppCatalogController::class, 'notifications']);
    Route::get('/announcements', [AppCatalogController::class, 'notifications']);
    Route::get('/brands', [AppCatalogController::class, 'brands']);

    // App registers FCM device token (can be guest or authenticated via Sanctum).
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
