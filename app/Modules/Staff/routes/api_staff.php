<?php

use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use Illuminate\Support\Facades\Route;
use App\Modules\Staff\Http\Controllers\Auth\AuthForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForStaffController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'abilities:actor:staff', 'permission:common.access'])->group(function () {

    // 인증
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForStaffController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForStaffController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForStaffController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'],'/password', [AuthForStaffController::class, 'updateMyPassword'])->name('password.update')
        ->middleware('throttle:6,1');


    // 대시보드
    Route::get('/dashboard', [DashboardForStaffController::class, 'getDashboardForStaff'])
        ->name('dashboard');

    // 병원 목록
    Route::get('hospitals', [HospitalForStaffController::class, 'getHospitalsForStaff'])
        ->name('hospitals.getHospitalsForStaff');

    // 병원 단건 조회
    Route::get('hospitals/{hospital}', [HospitalForStaffController::class, 'getHospitalForStaff'])
        ->name('hospitals.getHospitalForStaff');

    // 병원 생성
    Route::post('hospitals', [HospitalForStaffController::class, 'storeHospitalForStaff'])
        ->name('hospitals.storeHospitalForStaff');

    // 병원 수정
    Route::match(['post', 'put', 'patch'], 'hospitals/{hospital}', [HospitalForStaffController::class, 'updateHospitalForStaff'])
        ->name('hospitals.updateHospitalForStaff');

    // 병원 삭제
    Route::delete('hospitals/{hospital}', [HospitalForStaffController::class, 'deleteHospitalForStaff'])
        ->name('hospitals.deleteHospitalForStaff');
});
