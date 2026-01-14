<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// 관리자 API
Route::prefix('admin')
    ->middleware(['web','auth:admin'])
    ->group(function () {
        require __DIR__.'/settings.php';
    });
