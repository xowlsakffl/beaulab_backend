<?php

namespace App\Modules\User\Http\Middleware;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('user')->user();

        if (! $user instanceof AccountUser) {
            throw new CustomException(ErrorCode::UNAUTHORIZED);
        }

        if (! $user->isActive()) {
            throw new CustomException(ErrorCode::FORBIDDEN, '활성 상태의 사용자만 이용할 수 있습니다.');
        }

        return $next($request);
    }
}
