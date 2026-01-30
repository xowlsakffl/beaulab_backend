<?php

namespace App\Shared\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestId
{
    public function handle(Request $request, Closure $next)
    {
        $traceId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        // request에서 어디서든 꺼낼 수 있게
        $request->attributes->set('traceId', $traceId);

        // response에도 내려줌
        $response = $next($request);
        $response->headers->set('X-Request-Id', $traceId);

        return $response;
    }
}
