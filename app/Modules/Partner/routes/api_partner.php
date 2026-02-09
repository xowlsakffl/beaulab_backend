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

        // 내 병원 데이터 (병원 회원 전용)
        Route::get('/hospital', [HospitalForPartnerController::class, 'apiGetMyHospitalForHospital'])
            ->middleware('permission:hospital.profile.show')
            ->name('hospital.apiGetMyHospitalForHospital');

        // 내 병원 수정 (병원 회원 전용)
        Route::match(['put', 'patch'], '/hospital', [HospitalForPartnerController::class, 'apiUpdateMyHospitalForHospital'])
            ->middleware('permission:hospital.profile.update')
            ->name('hospital.update');

        // 내 병원 정보 조회 (병원회원 전용)
        Route::get('/hospital/my', [HospitalForPartnerController::class, 'myHospitalForHospital'])
            ->middleware('permission:hospital.profile.show')
            ->name('hospital.myHospitalForHospital');
    });
});
