<?php

namespace App\Common\Http\Responses;

use App\Common\Exceptions\ErrorCode;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        mixed $meta = null,
        ?string $traceId = null,
        int $status = 200
    ): JsonResponse {
        $traceId ??= self::traceId();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'traceId' => $traceId,
        ], $status);
    }

    /**
     *  ErrorCode 기반 에러 응답
     */
    public static function errorCode(
        ErrorCode $code,
        ?string $message = null,
        mixed $details = null,
        ?string $traceId = null,
        ?int $status = null
    ): JsonResponse {
        $traceId ??= self::traceId();

        return self::error(
            code: $code->value,
            message: $message ?? $code->messageApp(),
            details: $details,
            traceId: $traceId,
            status: $status ?? $code->status()
        );
    }

    /**
     * 저수준 에러 응답
     */
    public static function error(
        string $code,
        string $message,
        mixed $details = null,
        ?string $traceId = null,
        int $status = 400
    ): JsonResponse {
        $traceId ??= self::traceId();

        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
            'traceId' => $traceId,
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    private static function traceId(): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        /** @var \Illuminate\Http\Request $req */
        $req = app('request');

        return $req->attributes->get('traceId');
    }
}
