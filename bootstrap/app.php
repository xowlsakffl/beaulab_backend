<?php

use App\Common\Exceptions\AdminExceptionRenderer;
use App\Common\Exceptions\ApiExceptionRenderer;
use App\Common\Http\Middleware\RequestId;
use App\Modules\Admin\Http\Middleware\HandleAppearance;
use App\Modules\Admin\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../app/Modules/Admin/routes/web.php',   // 관리자 React(Inertia)
        api: __DIR__.'/../app/Modules/User/routes/api.php',   // 앱 사용자 API
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        /*then: function () {

        }*/
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    ->withMiddleware(function (Middleware $middleware): void {

        // spatie permission
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // 쿠키 암호화 예외
        $middleware->encryptCookies(except: [
            'appearance',
            'sidebar_state',
        ]);

        $middleware->prepend(RequestId::class);

        // Inertia(web) 전용 미들웨어
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    */
    ->withExceptions(function (Exceptions $exceptions): void {

        // JSON 강제는 /api/*만
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));

        $exceptions->context(function () {
            return [
                'traceId' => request()?->attributes->get('traceId'),
                'path'    => request()?->path(),
                'method'  => request()?->method(),
            ];
        });

        $adminRenderer = app(AdminExceptionRenderer::class);
        $apiRenderer   = app(ApiExceptionRenderer::class);

        // Admin 먼저(= /admin/*는 JSON 떨어질 여지 차단)
        $exceptions->render(function (\Throwable $e, Request $request) use ($adminRenderer) {
            return $adminRenderer->render($e, $request);
        });

        // 그 다음 API
        $exceptions->render(function (\Throwable $e, Request $request) use ($apiRenderer) {
            return $apiRenderer->render($e, $request);
        });
    })

    ->create();
