<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('staff')
        ->name('staff.')
        ->group(base_path('app/Modules/Staff/routes/api_staff.php'));

    Route::prefix('partner')
        ->name('partner.')
        ->group(base_path('app/Modules/Partner/routes/api_partner.php'));

    Route::prefix('user')
        ->name('user.')
        ->group(base_path('app/Modules/User/routes/api_user.php'));
});
