<?php

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Http\Middleware\RequestId;
use App\Common\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorized;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // API-only
        // web: __DIR__.'/../app/Modules/Admin/routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RequestId::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })

    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    */
    ->withExceptions(function (Exceptions $exceptions): void {

        // API-only면 항상 JSON (web 남기면 api/*로 좁혀도 됨)
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);

        // console에서도 안전한 context
        $exceptions->context(function () {
            if (!app()->bound('request')) {
                return [];
            }

            /** @var \Illuminate\Http\Request $req */
            $req = app('request');

            return [
                'traceId' => $req->attributes->get('traceId'),
                'path' => $req->path(),
                'method' => $req->method(),
            ];
        });

        // 예외 → ApiResponse 매핑
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof CustomException) {
                return ApiResponse::errorCode(
                    $e->errorCode,
                    message: $e->getMessage() !== '' ? $e->getMessage() : null,
                    details: $e->details
                );
            }

            // 422 Validation
            if ($e instanceof ValidationException) {
                return ApiResponse::errorCode(
                    ErrorCode::INVALID_REQUEST,
                    details: ['errors' => $e->errors()]
                );
            }

            // 401 Auth
            if ($e instanceof AuthenticationException) {
                return ApiResponse::errorCode(ErrorCode::UNAUTHORIZED);
            }

            // 403 Permission (Spatie)
            if ($e instanceof SpatieUnauthorized) {
                return ApiResponse::errorCode(ErrorCode::FORBIDDEN);
            }

            // 403 Authorization (Policy)
            if ($e instanceof AuthorizationException) {
                return ApiResponse::errorCode(ErrorCode::FORBIDDEN);
            }

            // 404 Not found (route/model)
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::errorCode(ErrorCode::NOT_FOUND);
            }

            // 405
            if ($e instanceof MethodNotAllowedHttpException) {
                return ApiResponse::errorCode(ErrorCode::METHOD_NOT_ALLOWED);
            }

            // DB
            if ($e instanceof QueryException) {
                $details = config('app.debug')
                    ? ['sql' => $e->getSql(), 'bindings' => $e->getBindings()]
                    : null;

                return ApiResponse::errorCode(ErrorCode::DB_ERROR, details: $details);
            }

            // abort(429), abort(419) 등 HTTP 예외
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();

                return match ($status) {
                    401 => ApiResponse::errorCode(ErrorCode::UNAUTHORIZED),
                    403 => ApiResponse::errorCode(ErrorCode::FORBIDDEN),
                    404 => ApiResponse::errorCode(ErrorCode::NOT_FOUND),
                    405 => ApiResponse::errorCode(ErrorCode::METHOD_NOT_ALLOWED),
                    419 => ApiResponse::errorCode(ErrorCode::TOKEN_ERROR),
                    422 => ApiResponse::errorCode(ErrorCode::INVALID_REQUEST),
                    429 => ApiResponse::errorCode(ErrorCode::RATE_LIMITED),
                    default => ApiResponse::errorCode(
                        ErrorCode::INTERNAL_ERROR,
                        message: $e->getMessage() !== '' ? $e->getMessage() : ErrorCode::INTERNAL_ERROR->messageApp(),
                        status: $status
                    ),
                };
            }

            // Fallback 500
            $details = config('app.debug')
                ? ['exception' => class_basename($e), 'message' => $e->getMessage()]
                : null;

            return ApiResponse::errorCode(ErrorCode::INTERNAL_ERROR, details: $details);
        });
    })

    ->create();
