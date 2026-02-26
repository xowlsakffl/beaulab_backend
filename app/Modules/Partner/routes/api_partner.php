<?php

use App\Modules\Partner\Http\Controllers\Dashboard\DashboardForPartnerController;
use App\Modules\Partner\Http\Controllers\Hospital\HospitalForPartnerController;
use Illuminate\Support\Facades\Route;

Route::prefix('partner')->name('partner.')->group(function () {
    Route::middleware(['web', 'auth:admin'])->group(function () {
        // 대시보드
        Route::get('', [DashboardForPartnerController::class, 'indexDashboard'])
            ->middleware('permission:common.dashboard.show')
            ->name('dashboard');

    });
});
