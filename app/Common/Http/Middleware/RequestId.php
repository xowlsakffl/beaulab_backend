<?php

namespace App\Common\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        // request에서 어디서든 꺼낼 수 있게
        $request->attributes->set('traceId', $traceId);

        Log::withContext(['traceId' => $traceId]);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // response에도 내려줌
        $response->headers->set('X-Request-Id', $traceId);

        return $response;
    }
}
