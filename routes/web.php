<?php

use App\Modules\Staff\Http\Controllers\DeveloperTool\DeveloperToolAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal_tool.ip')->group(function () {
    Route::get('/staff/tools', [DeveloperToolAuthController::class, 'index'])
        ->middleware('auth:tool_staff')
        ->name('tool.index');

    Route::get('/staff/tools/login', [DeveloperToolAuthController::class, 'showLoginForm'])
        ->name('tool.login');

    Route::post('/staff/tools/login', [DeveloperToolAuthController::class, 'login'])
        ->name('tool.login.submit');

    Route::match(['get', 'post'], '/staff/tools/logout', [DeveloperToolAuthController::class, 'logout'])
        ->middleware('auth:tool_staff')
        ->name('tool.logout');

    Route::redirect('/staff/horizon/login', '/staff/tools/login');
    Route::redirect('/staff/horizon/logout', '/staff/tools/logout');
});
