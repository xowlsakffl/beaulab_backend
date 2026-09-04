<?php

namespace App\Common\Http\Middleware;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActor
{
    public function handle(Request $request, Closure $next, string $name): Response
    {
        $actor = AuthActor::from($name);
        $model = $actor->model();
        $account = $request->user();
        if (! $account instanceof $model || ! $account->isActive()) {
            throw new AuthenticationException;
        }

        $webActor = $request->attributes->get(ActorAuthentication::ACTOR_ATTRIBUTE);
        if ($webActor !== $actor && ($actor !== AuthActor::USER || ! $account->tokenCan('actor:'.$name))) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
