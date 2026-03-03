<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('staff')
        ->name('staff.')
        ->group(base_path('app/Modules/Staff/routes/api_staff.php'));

    Route::prefix('hospital')
        ->name('hospital.')
        ->group(base_path('app/Modules/Hospital/routes/api_hospital.php'));

    Route::prefix('beauty')
        ->name('beauty.')
        ->group(base_path('app/Modules/Beauty/routes/api_beauty.php'));

    Route::prefix('user')
        ->name('user.')
        ->group(base_path('app/Modules/User/routes/api_user.php'));
});
