<?php

use App\Modules\Admin\Http\Controllers\Pages\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // 대시보드(홈)
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // dashboard / settings (Inertia 페이지들)
        require __DIR__.'/settings.php';
    });
});

