<?php

use App\Modules\Staff\Http\Controllers\AccountUser\AccountUserForStaffController;
use App\Modules\Staff\Http\Controllers\Auth\AuthForStaffController;
use App\Modules\Staff\Http\Controllers\Beauty\BeautyForStaffController;
use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalDoctor\DoctorForStaffController;
use App\Modules\Staff\Http\Controllers\BeautyExpert\ExpertForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalVideoRequest\VideoRequestForStaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForStaffController::class, 'login'])->name('login')->middleware('throttle:6,1');
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

    /**
     * 뷰티전문가 관리
     **/
    Route::get('experts', [ExpertForStaffController::class, 'getExpertsForStaff'])
        ->name('experts.getExpertsForStaff');
    Route::get('experts/{expert}', [ExpertForStaffController::class, 'getExpertForStaff'])
        ->name('experts.getExpertForStaff');
    Route::post('experts', [ExpertForStaffController::class, 'storeExpertForStaff'])
        ->name('experts.storeExpertForStaff');
    Route::match(['post', 'put', 'patch'], 'experts/{expert}', [ExpertForStaffController::class, 'updateExpertForStaff'])
        ->name('experts.updateExpertForStaff');
    Route::delete('experts/{expert}', [ExpertForStaffController::class, 'deleteExpertForStaff'])
        ->name('experts.deleteExpertForStaff');

    /**
     * 동영상 검수 신청 관리
     **/
    Route::get('video-requests', [VideoRequestForStaffController::class, 'getVideoRequestsForStaff'])
        ->name('videoRequests.getVideoRequestsForStaff');
    Route::get('video-requests/{videoRequest}', [VideoRequestForStaffController::class, 'getVideoRequestForStaff'])
        ->name('videoRequests.getVideoRequestForStaff');
    Route::match(['post', 'put', 'patch'], 'video-requests/{videoRequest}', [VideoRequestForStaffController::class, 'updateVideoRequestForStaff'])
        ->name('videoRequests.updateVideoRequestForStaff');
    Route::delete('video-requests/{videoRequest}', [VideoRequestForStaffController::class, 'deleteVideoRequestForStaff'])
        ->name('videoRequests.deleteVideoRequestForStaff');
});
