<?php

/**
 * 병원 계정 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Modules\Hospital\Http\Controllers\AdminNote\AdminNoteForHospitalController;
use App\Modules\Hospital\Http\Controllers\Auth\AuthForHospitalController;
use App\Modules\Hospital\Http\Controllers\Auth\HospitalAccountInvitationForHospitalController;
use App\Modules\Hospital\Http\Controllers\HospitalVideo\HospitalVideoForHospitalController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForHospitalController::class, 'login'])->name('login')->middleware('throttle:auth-login');
    Route::get('account-invitations/{token}', [HospitalAccountInvitationForHospitalController::class, 'getHospitalAccountInvitationForHospital'])
        ->name('account-invitations.getHospitalAccountInvitationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:hospital-account-invitation-verify');
    Route::post('account-invitations/{token}/phone-verifications', [HospitalAccountInvitationForHospitalController::class, 'sendHospitalAccountPhoneVerificationForHospital'])
        ->name('account-invitations.sendHospitalAccountPhoneVerificationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:hospital-account-phone-verification-send');
    Route::post('account-invitations/{token}/phone-verifications/{phoneVerification}/verify', [HospitalAccountInvitationForHospitalController::class, 'verifyHospitalAccountPhoneVerificationForHospital'])
        ->name('account-invitations.verifyHospitalAccountPhoneVerificationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->whereNumber('phoneVerification')
        ->middleware('throttle:hospital-account-phone-verification-verify');
    Route::post('account-invitations/{token}', [HospitalAccountInvitationForHospitalController::class, 'completeHospitalAccountInvitationForHospital'])
        ->name('account-invitations.completeHospitalAccountInvitationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:hospital-account-invitation-complete');
});

Route::middleware(['auth:sanctum', 'abilities:actor:hospital'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForHospitalController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForHospitalController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/password', [AuthForHospitalController::class, 'updateMyPassword'])->name('password.update')->middleware('throttle:password-update');
    Route::get('/notes', [AdminNoteForHospitalController::class, 'getAdminNotesForHospital'])->name('notes.getAdminNotesForHospital');
    Route::post('/notes', [AdminNoteForHospitalController::class, 'createAdminNoteForHospital'])->name('notes.createAdminNoteForHospital');
    Route::match(['put', 'patch'], '/notes/{note}', [AdminNoteForHospitalController::class, 'updateAdminNoteForHospital'])->name('notes.updateAdminNoteForHospital');

    /**
     * 동영상 공개여부 변경
     **/
    Route::patch('/videos/{video}/hospital-status', [HospitalVideoForHospitalController::class, 'updateVideoStatusForHospital'])
        ->name('videos.updateVideoStatusForHospital');

});
