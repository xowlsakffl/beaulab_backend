<?php

use App\Modules\Partner\Http\Controllers\Auth\AuthForPartnerController;
use App\Modules\Partner\Http\Controllers\VideoRequest\VideoRequestForPartnerController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForPartnerController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'abilities:actor:partner'])->group(function () {
    // 인증
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForPartnerController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForPartnerController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForPartnerController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForPartnerController::class, 'updateMyPassword'])->name('password.update')
        ->middleware('throttle:6,1');

    /**
     * 파트너 동영상 검수 신청
     */
    Route::get('video-requests', [VideoRequestForPartnerController::class, 'getVideoRequestsForPartner'])
        ->name('videoRequests.getVideoRequestsForPartner');
    Route::post('video-requests', [VideoRequestForPartnerController::class, 'storeVideoRequestForPartner'])
        ->name('videoRequests.storeVideoRequestForPartner');
    Route::match(['post', 'put', 'patch'], 'video-requests/{videoRequest}', [VideoRequestForPartnerController::class, 'updateVideoRequestForPartner'])
        ->name('videoRequests.updateVideoRequestForPartner');
    Route::patch('video-requests/{videoRequest}/cancel', [VideoRequestForPartnerController::class, 'cancelVideoRequestForPartner'])
        ->name('videoRequests.cancelVideoRequestForPartner');
});
