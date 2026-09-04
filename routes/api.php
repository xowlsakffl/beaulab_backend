<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    foreach (\App\Common\Auth\AuthActor::cases() as $actor) {
        Route::prefix($actor->value.'/auth')->middleware('web.session')->group(function () use ($actor) {
            Route::get('csrf', [\App\Common\Http\Controllers\WebSessionController::class, 'csrf']);
            Route::middleware(['auth:sanctum', 'actor:'.$actor->value])->group(function () {
                Route::get('session', [\App\Common\Http\Controllers\WebSessionController::class, 'status']);
                Route::post('activity', [\App\Common\Http\Controllers\WebSessionController::class, 'status']);
            });
        });
    }

    Route::post('user/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate'])
        ->middleware(['web.session', 'auth:sanctum', 'actor:user']);

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
