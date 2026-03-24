<?php

use App\Modules\Hospital\Http\Controllers\Auth\AuthForHospitalController;
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

    /**
     * 동영상 게시 요청
     **/
    Route::post('/videos', [HospitalVideoForHospitalController::class, 'storeVideoForHospital'])->name('videos.storeVideoForHospital');
    Route::post('/videos/{video}/cancel', [HospitalVideoForHospitalController::class, 'cancelVideoForHospital'])->name('videos.cancelVideoForHospital');

});
