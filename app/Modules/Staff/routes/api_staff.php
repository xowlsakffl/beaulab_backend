<?php

use App\Modules\Staff\Http\Controllers\AccountUser\AccountUserForStaffController;
use App\Modules\Staff\Http\Controllers\Auth\AuthForStaffController;
use App\Modules\Staff\Http\Controllers\Beauty\BeautyForStaffController;
use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use App\Modules\Staff\Http\Controllers\Doctor\DoctorForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;
use Illuminate\Support\Facades\Route;

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
    Route::match(['put', 'patch'], '/password', [AuthForStaffController::class, 'updateMyPassword'])->name('password.update')
        ->middleware('throttle:6,1');

    // 대시보드
    Route::get('/dashboard', [DashboardForStaffController::class, 'getDashboardForStaff'])
        ->name('dashboard');

    /**
     * 병원 관리
     **/
    Route::get('hospitals', [HospitalForStaffController::class, 'getHospitalsForStaff'])
        ->name('hospitals.getHospitalsForStaff');
    Route::get('hospitals/{hospital}', [HospitalForStaffController::class, 'getHospitalForStaff'])
        ->name('hospitals.getHospitalForStaff');
    Route::post('hospitals', [HospitalForStaffController::class, 'storeHospitalForStaff'])
        ->name('hospitals.storeHospitalForStaff');
    Route::match(['post', 'put', 'patch'], 'hospitals/{hospital}', [HospitalForStaffController::class, 'updateHospitalForStaff'])
        ->name('hospitals.updateHospitalForStaff');
    Route::delete('hospitals/{hospital}', [HospitalForStaffController::class, 'deleteHospitalForStaff'])
        ->name('hospitals.deleteHospitalForStaff');

    /**
     * 뷰티 관리
     **/
    Route::get('beauties', [BeautyForStaffController::class, 'getBeautiesForStaff'])
        ->name('beauties.getBeautiesForStaff');
    Route::get('beauties/{beauty}', [BeautyForStaffController::class, 'getBeautyForStaff'])
        ->name('beauties.getBeautyForStaff');
    Route::post('beauties', [BeautyForStaffController::class, 'storeBeautyForStaff'])
        ->name('beauties.storeBeautyForStaff');
    Route::match(['post', 'put', 'patch'], 'beauties/{beauty}', [BeautyForStaffController::class, 'updateBeautyForStaff'])
        ->name('beauties.updateBeautyForStaff');
    Route::delete('beauties/{beauty}', [BeautyForStaffController::class, 'deleteBeautyForStaff'])
        ->name('beauties.deleteBeautyForStaff');

    /**
     * 일반회원 관리
     **/
    Route::get('users', [AccountUserForStaffController::class, 'getAccountUsersForStaff'])
        ->name('users.getAccountUsersForStaff');
    Route::get('users/{user}', [AccountUserForStaffController::class, 'getAccountUserForStaff'])
        ->name('users.getAccountUserForStaff');
    Route::match(['post', 'put', 'patch'], 'users/{user}', [AccountUserForStaffController::class, 'updateAccountUserForStaff'])
        ->name('users.updateAccountUserForStaff');
    Route::delete('users/{user}', [AccountUserForStaffController::class, 'deleteAccountUserForStaff'])
        ->name('users.deleteAccountUserForStaff');

    /**
     * 의사 관리
     **/
    Route::get('doctors', [DoctorForStaffController::class, 'getDoctorsForStaff'])
        ->name('doctors.getDoctorsForStaff');
    Route::get('doctors/{doctor}', [DoctorForStaffController::class, 'getDoctorForStaff'])
        ->name('doctors.getDoctorForStaff');
    Route::post('doctors', [DoctorForStaffController::class, 'storeDoctorForStaff'])
        ->name('doctors.storeDoctorForStaff');
    Route::match(['post', 'put', 'patch'], 'doctors/{doctor}', [DoctorForStaffController::class, 'updateDoctorForStaff'])
        ->name('doctors.updateDoctorForStaff');
    Route::delete('doctors/{doctor}', [DoctorForStaffController::class, 'deleteDoctorForStaff'])
        ->name('doctors.deleteDoctorForStaff');
});
