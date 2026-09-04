<?php

namespace App\Common\Http\Middleware;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireWebSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->attributes->get(ActorAuthentication::ACTOR_ATTRIBUTE) instanceof AuthActor) {
            throw new AuthenticationException('웹 세션 인증이 필요합니다.');
        }

        return $next($request);
    }
}
