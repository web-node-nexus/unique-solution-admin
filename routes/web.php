<?php

use App\Http\Controllers\PaymentCheckoutController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check() && Auth::user()?->isAdmin() && Auth::user()?->is_active) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
});

Route::get('/pay/{order}', [PaymentCheckoutController::class, 'show'])->name('pay.show');
Route::post('/pay/{order}/confirm', [PaymentCheckoutController::class, 'confirm'])->name('pay.confirm');
