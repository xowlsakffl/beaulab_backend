<?php

use App\Modules\Staff\Http\Controllers\Tool\ToolAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal_tool.ip')->group(function () {
    Route::get('/staff/tools', [ToolAuthController::class, 'index'])
        ->middleware('auth:tool_staff')
        ->name('tool.index');

    Route::get('/staff/tools/login', [ToolAuthController::class, 'showLoginForm'])
        ->name('tool.login');

    Route::post('/staff/tools/login', [ToolAuthController::class, 'login'])
        ->name('tool.login.submit');

    Route::match(['get', 'post'], '/staff/tools/logout', [ToolAuthController::class, 'logout'])
        ->middleware('auth:tool_staff')
        ->name('tool.logout');

    Route::redirect('/staff/horizon/login', '/staff/tools/login');
    Route::redirect('/staff/horizon/logout', '/staff/tools/logout');
});
