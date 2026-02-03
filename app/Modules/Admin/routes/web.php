<?php

use App\Modules\Admin\Http\Controllers\Dashboard\DashboardController;
use App\Modules\Admin\Http\Controllers\Hospital\HospitalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('auth:admin')->group(function () {
        // 대시보드
        Route::get('', [DashboardController::class, 'indexDashboard'])
            ->middleware('permission:common.dashboard.show')
            ->name('dashboard');

        /**
         * 병원 관리 뷰랩소속
         */
        // 병원 전체 목록 (직원 전용)
        Route::get('/hospitals', [HospitalController::class, 'indexHospitalPageForStaff'])
            ->middleware('permission:beaulab.hospital.list')
            ->name('hospitals.indexPageForStaff');

        /**
         * 내 병원 관리
         */
        // 내 병원 정보 조회 (병원회원 전용)
        Route::get('/hospital/my', [HospitalController::class, 'myHospitalForHospital'])
            ->middleware('permission:hospital.profile.show')
            ->name('hospital.myHospitalForHospital');



        // 설정
        require __DIR__.'/settings.php';
    });
});
