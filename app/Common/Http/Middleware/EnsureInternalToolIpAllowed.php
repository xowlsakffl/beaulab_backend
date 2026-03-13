<?php

namespace App\Common\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

final class EnsureInternalToolIpAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = $this->allowedIps();
        $clientIp = $request->ip();

        if ($clientIp === null || ! IpUtils::checkIp($clientIp, $allowedIps)) {
            abort(403, '허용되지 않은 내부도구 접속 IP입니다.');
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function allowedIps(): array
    {
        $configured = (string) env('INTERNAL_TOOL_ALLOWED_IPS', '127.0.0.1,::1');

        return array_values(array_filter(array_map(
            static fn (string $ip): string => trim($ip),
            explode(',', $configured)
        )));
    }
}
