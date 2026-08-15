<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check() && Auth::user()?->isAdmin() && Auth::user()?->is_active) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
});
