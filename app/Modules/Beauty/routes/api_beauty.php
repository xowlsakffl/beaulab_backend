<?php

/**
 * 뷰티 계정 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Modules\Beauty\Http\Controllers\AdminNote\AdminNoteForBeautyController;
use App\Modules\Beauty\Http\Controllers\Auth\AuthForBeautyController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForBeautyController::class, 'login'])->name('login')->middleware(['web.session', 'throttle:auth-login']);
    Route::post('password-reset-link', [AuthForBeautyController::class, 'sendPasswordResetLink'])
        ->name('password-reset-link')
        ->middleware('throttle:password-reset-link');
    Route::post('password-reset/verify', [AuthForBeautyController::class, 'verifyPasswordResetToken'])
        ->name('password-reset.verify')
        ->middleware('throttle:password-reset-verify');
    Route::post('password-reset', [AuthForBeautyController::class, 'resetPassword'])
        ->name('password-reset')
        ->middleware('throttle:password-reset-submit');
});

Route::middleware(['auth:sanctum', 'actor:beauty'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForBeautyController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForBeautyController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForBeautyController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForBeautyController::class, 'updateMyPassword'])->name('password.update')->middleware('throttle:password-update');
    Route::get('/notes', [AdminNoteForBeautyController::class, 'getAdminNotesForBeauty'])->name('notes.getAdminNotesForBeauty');
    Route::post('/notes', [AdminNoteForBeautyController::class, 'createAdminNoteForBeauty'])->name('notes.createAdminNoteForBeauty');
    Route::match(['put', 'patch'], '/notes/{note}', [AdminNoteForBeautyController::class, 'updateAdminNoteForBeauty'])->name('notes.updateAdminNoteForBeauty');

});
