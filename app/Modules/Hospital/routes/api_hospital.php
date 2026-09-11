<?php

/**
 * 병원 계정 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Modules\Hospital\Http\Controllers\AdminNote\AdminNoteForHospitalController;
use App\Modules\Hospital\Http\Controllers\Auth\AuthForHospitalController;
use App\Modules\Hospital\Http\Controllers\Auth\HospitalAccountInvitationForHospitalController;
use App\Modules\Hospital\Http\Controllers\Auth\HospitalAccountPasswordResetForHospitalController;
use App\Modules\Hospital\Http\Controllers\HospitalPromotion\HospitalPromotionForHospitalController;
use App\Modules\Hospital\Http\Controllers\HospitalVideo\HospitalVideoForHospitalController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('password-reset-link', [HospitalAccountPasswordResetForHospitalController::class, 'sendHospitalAccountPasswordReset'])
        ->name('password-reset-link')->middleware('throttle:password-reset-link');
    Route::post('password-reset/verify', [HospitalAccountPasswordResetForHospitalController::class, 'verifyHospitalAccountPasswordReset'])
        ->name('password-reset.verify')->middleware('throttle:password-reset-verify');
    Route::post('password-reset', [HospitalAccountPasswordResetForHospitalController::class, 'resetHospitalAccountPassword'])
        ->name('password-reset')->middleware('throttle:password-reset-submit');
    Route::post('login', [AuthForHospitalController::class, 'login'])->name('login')->middleware(['web.session', 'throttle:auth-login']);
    Route::get('account-invitations/{token}', [HospitalAccountInvitationForHospitalController::class, 'getHospitalAccountInvitationForHospital'])
        ->name('account-invitations.getHospitalAccountInvitationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:hospital-account-invitation-verify');
    Route::post('account-invitations/{token}/email-verifications', [HospitalAccountInvitationForHospitalController::class, 'sendHospitalAccountEmailVerificationForHospital'])
        ->name('account-invitations.sendHospitalAccountEmailVerificationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:hospital-account-email-verification-send');
    Route::post('account-invitations/{token}/email-verifications/{emailVerification}/verify', [HospitalAccountInvitationForHospitalController::class, 'verifyHospitalAccountEmailVerificationForHospital'])
        ->name('account-invitations.verifyHospitalAccountEmailVerificationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->whereNumber('emailVerification')
        ->middleware('throttle:hospital-account-email-verification-verify');
    Route::post('account-invitations/{token}', [HospitalAccountInvitationForHospitalController::class, 'completeHospitalAccountInvitationForHospital'])
        ->name('account-invitations.completeHospitalAccountInvitationForHospital')
        ->where('token', '[A-Za-z0-9]{64}')
        ->middleware('throttle:hospital-account-invitation-complete');
});

Route::middleware(['auth:sanctum', 'actor:hospital'])->group(function () {
    Route::get('promotions', [HospitalPromotionForHospitalController::class, 'index'])->name('promotions.index');
    Route::get('promotions/{promotion}', [HospitalPromotionForHospitalController::class, 'show'])->whereNumber('promotion')->name('promotions.show');
    Route::post('promotions/{promotion}/click', [HospitalPromotionForHospitalController::class, 'click'])
        ->whereNumber('promotion')->middleware('throttle:hospital-promotion-click')->name('promotions.click');

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
