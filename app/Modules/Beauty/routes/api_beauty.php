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

});
