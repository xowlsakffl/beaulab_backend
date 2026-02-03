<?php

namespace App\Common\Http;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, mixed $meta = null, ?string $traceId = null, int $status = 200): JsonResponse
    {
        $traceId ??= request()?->attributes?->get('traceId');

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'traceId' => $traceId,
        ], $status);
    }

    public static function error(string $code, string $message, mixed $details = null, ?string $traceId = null, int $status = 400): JsonResponse
    {
        $traceId ??= request()?->attributes?->get('traceId');

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
}
