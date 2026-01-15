<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// 관리자 API
Route::prefix('admin/api')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {
        // JSON API만 둠
        // Route::get('users', ...);
    });
