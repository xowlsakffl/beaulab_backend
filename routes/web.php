<?php

use App\Modules\Admin\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // 대시보드
        Route::get('', DashboardController::class)->name('dashboard');

        // dashboard / settings (Inertia 페이지들)
        require __DIR__.'/settings.php';

        Route::get('/report', function () {
            return Inertia::render('admin/report');
        })->name('report');

        Route::get('/calendar', function () {
            return Inertia::render('admin/calendar');
        })->name('calendar');

        Route::get('/form', function () {
            return Inertia::render('admin/form');
        })->name('form');

        Route::get('/ui-preview', function () {
            return Inertia::render('admin/ui-preview');
        })->name('admin.ui-preview');
    });
});
