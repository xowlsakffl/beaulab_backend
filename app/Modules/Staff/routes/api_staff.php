<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Staff\Http\Controllers\Auth\AuthForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForStaffController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'abilities:actor:staff'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForStaffController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForStaffController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForStaffController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'],'/password', [AuthForStaffController::class, 'updateMyPassword'])->name('password.update')
        ->middleware('throttle:6,1');

    // 병원 목록
    Route::get('hospitals', [HospitalForStaffController::class, 'getHospitalsForStaff'])
        ->middleware('permission:beaulab.hospital.list')
        ->name('hospitals.getHospitalsForStaff');

    // 병원 생성
    Route::post('hospitals', [HospitalForStaffController::class, 'storeHospitalForStaff'])
        ->middleware('permission:beaulab.hospital.create')
        ->name('hospitals.storeHospitalForStaff');

    // 병원 수정
    Route::match(['put', 'patch'], 'hospitals/{hospital}', [HospitalForStaffController::class, 'updateHospitalForStaff'])
        ->middleware('permission:beaulab.hospital.update')
        ->name('hospitals.updateHospitalForStaff');

    // 병원 삭제
    Route::delete('hospitals/{hospital}', [HospitalForStaffController::class, 'deleteHospitalForStaff'])
        ->middleware('permission:beaulab.hospital.delete')
        ->name('hospitals.deleteHospitalForStaff');
});
