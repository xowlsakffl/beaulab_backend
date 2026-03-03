<?php

use App\Modules\Beauty\Http\Controllers\Auth\AuthForBeautyController;
use App\Modules\Beauty\Http\Controllers\VideoRequest\VideoRequestForBeautyController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForBeautyController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'abilities:actor:beauty'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForBeautyController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForBeautyController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForBeautyController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForBeautyController::class, 'updateMyPassword'])->name('password.update')->middleware('throttle:6,1');

    Route::get('video-requests', [VideoRequestForBeautyController::class, 'getVideoRequestsForBeauty'])->name('videoRequests.getVideoRequestsForBeauty');
    Route::get('video-requests/{videoRequest}', [VideoRequestForBeautyController::class, 'getVideoRequestForBeauty'])->name('videoRequests.getVideoRequestForBeauty');
    Route::post('video-requests', [VideoRequestForBeautyController::class, 'storeVideoRequestForBeauty'])->name('videoRequests.storeVideoRequestForBeauty');
    Route::match(['post', 'put', 'patch'], 'video-requests/{videoRequest}', [VideoRequestForBeautyController::class, 'updateVideoRequestForBeauty'])->name('videoRequests.updateVideoRequestForBeauty');
    Route::patch('video-requests/{videoRequest}/cancel', [VideoRequestForBeautyController::class, 'cancelVideoRequestForBeauty'])->name('videoRequests.cancelVideoRequestForBeauty');
});
