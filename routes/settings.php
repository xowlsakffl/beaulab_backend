<?php

use App\Modules\Admin\Http\Controllers\Settings\AppearanceController;
use App\Modules\Admin\Http\Controllers\Settings\PasswordController;
use App\Modules\Admin\Http\Controllers\Settings\ProfileController;
use App\Modules\Admin\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin'])->group(function () {
    Route::redirect('settings', 'admin/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('admin-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('admin-password.update');

    Route::get('settings/appearance', AppearanceController::class)->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])->name('two-factor.show');
});
