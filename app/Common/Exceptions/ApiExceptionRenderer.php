<?php

namespace App\Common\Exceptions;

use App\Common\Http\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class ApiExceptionRenderer
{
    public function handles(Request $request): bool
    {
        return $request->is('api/*');
    }

    public function render(\Throwable $e, Request $request): Response|null
    {
        if (! $this->handles($request)) {
            return null;
        }

        $traceId = $request->attributes->get('traceId');

        // 422 Validation
        if ($e instanceof ValidationException) {
            return ApiResponse::error(
                ApiErrorCode::INVALID_REQUEST->value,
                ApiErrorCode::INVALID_REQUEST->messageApp(),
                $e->errors(),
                $traceId,
                ApiErrorCode::INVALID_REQUEST->status()
            );
        }

        // 401 Auth
        if ($e instanceof AuthenticationException) {
            return ApiResponse::error(
                ApiErrorCode::UNAUTHORIZED->value,
                ApiErrorCode::UNAUTHORIZED->messageApp(),
                null,
                $traceId,
                ApiErrorCode::UNAUTHORIZED->status()
            );
        }

        // 403 Authorization
        if ($e instanceof AuthorizationException) {
            return ApiResponse::error(
                ApiErrorCode::FORBIDDEN->value,
                ApiErrorCode::FORBIDDEN->messageApp(),
                null,
                $traceId,
                ApiErrorCode::FORBIDDEN->status()
            );
        }

        // 404 Model not found
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::error(
                ApiErrorCode::NOT_FOUND->value,
                ApiErrorCode::NOT_FOUND->messageApp(),
                null,
                $traceId,
                ApiErrorCode::NOT_FOUND->status()
            );
        }

        // Custom business exception
        if ($e instanceof CustomException) {
            $message = $e->getMessage() !== ''
                ? $e->getMessage()
                : $e->errorCode->messageApp();

            return ApiResponse::error(
                $e->errorCode->value,
                $message,
                $e->details,
                $traceId,
                $e->errorCode->status()
            );
        }

        // DB error
        if ($e instanceof QueryException) {
            $details = config('app.debug')
                ? ['sql' => $e->getSql(), 'bindings' => $e->getBindings()]
                : null;

            return ApiResponse::error(
                ApiErrorCode::DB_ERROR->value,
                ApiErrorCode::DB_ERROR->messageApp(),
                $details,
                $traceId,
                ApiErrorCode::DB_ERROR->status()
            );
        }

        // HTTP exceptions (abort(404), 419, 405...)
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            $code = match ($status) {
                401 => ApiErrorCode::UNAUTHORIZED->value,
                403 => ApiErrorCode::FORBIDDEN->value,
                404 => ApiErrorCode::NOT_FOUND->value,
                405 => ApiErrorCode::METHOD_NOT_ALLOWED->value,
                419 => ApiErrorCode::TOKEN_ERROR->value,
                422 => ApiErrorCode::INVALID_REQUEST->value,
                default => ApiErrorCode::INTERNAL_ERROR->value,
            };

            $message = match ($status) {
                401 => ApiErrorCode::UNAUTHORIZED->messageApp(),
                403 => ApiErrorCode::FORBIDDEN->messageApp(),
                404 => ApiErrorCode::NOT_FOUND->messageApp(),
                405 => ApiErrorCode::METHOD_NOT_ALLOWED->messageApp(),
                419 => ApiErrorCode::TOKEN_ERROR->messageApp(),
                422 => ApiErrorCode::INVALID_REQUEST->messageApp(),
                default => ApiErrorCode::INTERNAL_ERROR->messageApp(),
            };

            return ApiResponse::error($code, $message, null, $traceId, $status);
        }

        // Fallback 500
        $details = config('app.debug')
            ? ['exception' => class_basename($e), 'message' => $e->getMessage()]
            : null;

        return ApiResponse::error(
            ApiErrorCode::INTERNAL_ERROR->value,
            ApiErrorCode::INTERNAL_ERROR->messageApp(),
            $details,
            $traceId,
            500
        );
    }
}
