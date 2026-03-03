<?php

use App\Modules\Hospital\Http\Controllers\Auth\AuthForHospitalController;
use App\Modules\Hospital\Http\Controllers\VideoRequest\VideoRequestForHospitalController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForHospitalController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'abilities:actor:hospital'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForHospitalController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForHospitalController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForHospitalController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForHospitalController::class, 'updateMyPassword'])->name('password.update')->middleware('throttle:6,1');

    Route::get('video-requests', [VideoRequestForHospitalController::class, 'getVideoRequestsForHospital'])->name('videoRequests.getVideoRequestsForHospital');
    Route::get('video-requests/{videoRequest}', [VideoRequestForHospitalController::class, 'getVideoRequestForHospital'])->name('videoRequests.getVideoRequestForHospital');
    Route::post('video-requests', [VideoRequestForHospitalController::class, 'storeVideoRequestForHospital'])->name('videoRequests.storeVideoRequestForHospital');
    Route::match(['post', 'put', 'patch'], 'video-requests/{videoRequest}', [VideoRequestForHospitalController::class, 'updateVideoRequestForHospital'])->name('videoRequests.updateVideoRequestForHospital');
    Route::patch('video-requests/{videoRequest}/cancel', [VideoRequestForHospitalController::class, 'cancelVideoRequestForHospital'])->name('videoRequests.cancelVideoRequestForHospital');
});
