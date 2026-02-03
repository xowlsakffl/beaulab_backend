<?php

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\ApiResponse;
use App\Common\Http\Middleware\RequestId;
use App\Modules\Admin\Http\Middleware\HandleAppearance;
use App\Modules\Admin\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../app/Modules/Admin/routes/web.php',   // 관리자 React(Inertia) 페이지
        api: __DIR__.'/../app/Modules/User/routes/api.php',   // 앱 사용자 API
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('app/Modules/Admin/routes/admin.php')); // 관리자 API (/admin/api/*)
        }
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    ->withMiddleware(function (Middleware $middleware): void {

        // spatie permission
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // 쿠키 암호화 예외
        $middleware->encryptCookies(except: [
            'appearance',
            'sidebar_state',
        ]);

        $middleware->prepend(RequestId::class);
        // traceId: web + api 모두 적용 (Inertia 페이지도 예외 추적)
        // $middleware->appendToGroup('api', [RequestId::class]);
        // $middleware->appendToGroup('web', [RequestId::class]);

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

        /**
         * 관리자 API 판별
         * - /admin/api/* 만 관리자 API
         * - /admin/* 페이지(Inertia)는 제외
         */
        $isAdminApiRequest = fn (Request $request) => $request->is('admin/api/*');

        /**
         * "JSON 에러 포맷(ApiResponse)으로 강제할 요청" 기준
         * - 앱 API: /api/*
         * - 관리자 API: /admin/api/*
         * - 또는 expectsJson() (React fetch/axios가 Accept: application/json 보내는 경우)
         */
        $isJsonApiRequest = function (Request $request): bool {
            return $request->is('api/*')
                || $request->is('admin/api/*')
                || $request->expectsJson();
        };

        /**
         * JSON 렌더링 기준
         * - 앱 API: /api/*
         * - 관리자 API: /admin/api/*
         * - 그 외는 Accept 헤더 기준 (Inertia 안전)
         */
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            if ($request->is('api/*') || $request->is('admin/api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        /**
         * 공통 로그 컨텍스트
         */
        $exceptions->context(function () {
            return [
                'traceId' => request()?->attributes->get('traceId'),
                'path'    => request()?->path(),
                'method'  => request()?->method(),
            ];
        });

        // Validation (422)
        $exceptions->render(function (ValidationException $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null; // Inertia 페이지는 기본 동작(redirect back + errors)
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            return ApiResponse::error(
                ErrorCode::INVALID_REQUEST->value,
                $isAdmin
                    ? ErrorCode::INVALID_REQUEST->messageAdmin()
                    : ErrorCode::INVALID_REQUEST->messageApp(),
                $e->errors(),
                $traceId,
                ErrorCode::INVALID_REQUEST->status()
            );
        });

        // Authentication / Authorization (401 / 403)
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null; // 페이지 접근은 redirect 로그인 흐름 유지
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            return ApiResponse::error(
                ErrorCode::UNAUTHORIZED->value,
                $isAdmin
                    ? ErrorCode::UNAUTHORIZED->messageAdmin()
                    : ErrorCode::UNAUTHORIZED->messageApp(),
                null,
                $traceId,
                ErrorCode::UNAUTHORIZED->status()
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null;
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            return ApiResponse::error(
                ErrorCode::FORBIDDEN->value,
                $isAdmin
                    ? ErrorCode::FORBIDDEN->messageAdmin()
                    : ErrorCode::FORBIDDEN->messageApp(),
                null,
                $traceId,
                ErrorCode::FORBIDDEN->status()
            );
        });

        // Model Not Found (404)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null;
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            return ApiResponse::error(
                ErrorCode::NOT_FOUND->value,
                $isAdmin
                    ? ErrorCode::NOT_FOUND->messageAdmin()
                    : ErrorCode::NOT_FOUND->messageApp(),
                null,
                $traceId,
                ErrorCode::NOT_FOUND->status()
            );
        });

        // Business Exception (Custom)
        $exceptions->render(function (CustomException $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null;
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            $message = $e->getMessage() !== ''
                ? $e->getMessage()
                : ($isAdmin
                    ? $e->errorCode->messageAdmin()
                    : $e->errorCode->messageApp());

            return ApiResponse::error(
                $e->errorCode->value,
                $message,
                $e->details,
                $traceId,
                $e->errorCode->status()
            );
        });

        // Database Error
        $exceptions->render(function (QueryException $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null;
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            $details = null;
            if ($isAdmin && config('app.debug')) {
                $details = [
                    'sql'      => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ];
            }

            return ApiResponse::error(
                ErrorCode::DB_ERROR->value,
                $isAdmin
                    ? ErrorCode::DB_ERROR->messageAdmin()
                    : ErrorCode::DB_ERROR->messageApp(),
                $details,
                $traceId,
                ErrorCode::DB_ERROR->status()
            );
        });

        // HTTP Exception (abort, 404, 419, etc)
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null;
            }

            $traceId = $request->attributes->get('traceId');
            $status  = $e->getStatusCode();
            $isAdmin = $isAdminApiRequest($request);

            $code = match ($status) {
                401 => ErrorCode::UNAUTHORIZED->value,
                403 => ErrorCode::FORBIDDEN->value,
                404 => ErrorCode::NOT_FOUND->value,
                405 => ErrorCode::METHOD_NOT_ALLOWED->value,
                419 => ErrorCode::TOKEN_ERROR->value,
                422 => ErrorCode::INVALID_REQUEST->value,
                default => ErrorCode::INTERNAL_ERROR->value,
            };

            $message = match ($status) {
                401 => $isAdmin ? ErrorCode::UNAUTHORIZED->messageAdmin() : ErrorCode::UNAUTHORIZED->messageApp(),
                403 => $isAdmin ? ErrorCode::FORBIDDEN->messageAdmin() : ErrorCode::FORBIDDEN->messageApp(),
                404 => $isAdmin ? ErrorCode::NOT_FOUND->messageAdmin() : ErrorCode::NOT_FOUND->messageApp(),
                405 => $isAdmin ? ErrorCode::METHOD_NOT_ALLOWED->messageAdmin() : ErrorCode::METHOD_NOT_ALLOWED->messageApp(),
                419 => $isAdmin ? ErrorCode::TOKEN_ERROR->messageAdmin() : ErrorCode::TOKEN_ERROR->messageApp(),
                422 => $isAdmin ? ErrorCode::INVALID_REQUEST->messageAdmin() : ErrorCode::INVALID_REQUEST->messageApp(),
                default => $isAdmin ? 'HTTP 오류가 발생했습니다.' : '요청을 처리할 수 없습니다.',
            };

            return ApiResponse::error($code, $message, null, $traceId, $status);
        });

        // Fallback (500)
        $exceptions->render(function (\Throwable $e, Request $request) use ($isAdminApiRequest, $isJsonApiRequest) {
            if (! $isJsonApiRequest($request)) {
                return null; // Inertia 페이지는 기본 500 처리 유지
            }

            $traceId = $request->attributes->get('traceId');
            $isAdmin = $isAdminApiRequest($request);

            $details = null;
            if ($isAdmin && config('app.debug')) {
                $details = [
                    'exception' => class_basename($e),
                    'message'   => $e->getMessage(),
                ];
            }

            return ApiResponse::error(
                ErrorCode::INTERNAL_ERROR->value,
                $isAdmin
                    ? '서버 오류(관리자) - 로그를 확인하세요.'
                    : ErrorCode::INTERNAL_ERROR->messageApp(),
                $details,
                $traceId,
                500
            );
        });
    })

    ->create();
