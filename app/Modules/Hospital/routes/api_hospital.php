<?php

/**
 * 병원 계정 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Modules\Hospital\Http\Controllers\Auth\AuthForHospitalController;
use App\Modules\Hospital\Http\Controllers\AdminNote\AdminNoteForHospitalController;
use App\Modules\Hospital\Http\Controllers\HospitalVideo\HospitalVideoForHospitalController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForHospitalController::class, 'login'])->name('login')->middleware('throttle:6,1');
});

Route::middleware(['auth:sanctum', 'abilities:actor:hospital'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForHospitalController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForHospitalController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForHospitalController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForHospitalController::class, 'updateMyPassword'])->name('password.update')->middleware('throttle:6,1');
    Route::get('/notes', [AdminNoteForHospitalController::class, 'getAdminNotesForHospital'])->name('notes.getAdminNotesForHospital');
    Route::post('/notes', [AdminNoteForHospitalController::class, 'createAdminNoteForHospital'])->name('notes.createAdminNoteForHospital');
    Route::match(['put', 'patch'], '/notes/{note}', [AdminNoteForHospitalController::class, 'updateAdminNoteForHospital'])->name('notes.updateAdminNoteForHospital');

    /**
     * 동영상 공개여부 변경
     **/
    Route::patch('/videos/{video}/hospital-status', [HospitalVideoForHospitalController::class, 'updateVideoStatusForHospital'])
        ->name('videos.updateVideoStatusForHospital');

});
