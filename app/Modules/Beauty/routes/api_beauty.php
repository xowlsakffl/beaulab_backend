<?php

/**
 * 뷰티 계정 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Modules\Beauty\Http\Controllers\Auth\AuthForBeautyController;
use App\Modules\Beauty\Http\Controllers\AdminNote\AdminNoteForBeautyController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForBeautyController::class, 'login'])->name('login')->middleware('throttle:6,1');
});

Route::middleware(['auth:sanctum', 'abilities:actor:beauty'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForBeautyController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForBeautyController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForBeautyController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForBeautyController::class, 'updateMyPassword'])->name('password.update')->middleware('throttle:6,1');
    Route::get('/notes', [AdminNoteForBeautyController::class, 'getAdminNotesForBeauty'])->name('notes.getAdminNotesForBeauty');
    Route::post('/notes', [AdminNoteForBeautyController::class, 'storeAdminNoteForBeauty'])->name('notes.storeAdminNoteForBeauty');
    Route::match(['put', 'patch'], '/notes/{note}', [AdminNoteForBeautyController::class, 'updateAdminNoteForBeauty'])->name('notes.updateAdminNoteForBeauty');

});
