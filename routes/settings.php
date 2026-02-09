<?php

use App\Common\Http\Controllers\settings\PasswordController;
use App\Common\Http\Controllers\settings\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/settings/profile', [ProfileController::class, 'show']);
    Route::patch('/settings/profile', [ProfileController::class, 'update']);
    Route::delete('/settings/profile', [ProfileController::class, 'destroy']); // 필요할 때만

    Route::put('/settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1');
});
