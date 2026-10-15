<?php

namespace App\Common\Http\Middleware;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Symfony\Component\HttpFoundation\Response;

final class StartActorWebSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('media.show') || $request->header('X-Beaulab-Client') !== 'web') {
            return $next($request);
        }

        $actor = $request->segment(1) === 'api' && $request->segment(2) === 'v1'
            ? AuthActor::tryFrom((string) $request->segment(3)) : null;
        if (! $actor || $request->bearerToken()) {
            throw new AuthorizationException;
        }

        $source = $request->header('Origin') ?: $request->header('Referer');
        $parts = $source ? parse_url($source) : false;
        $origin = is_array($parts) && isset($parts['scheme'], $parts['host'])
            ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '') : null;
        $policy = $actor->policy();
        if (! $origin || ! in_array($origin, $policy['origins'], true)
            || $request->header('Sec-Fetch-Site') === 'cross-site'
            || (! app()->environment(['local', 'testing']) && $parts['scheme'] !== 'https')) {
            throw new AuthorizationException;
        }

        $previousSession = config('session');
        $previousGuards = config('sanctum.guard');
        config([
            'session.driver' => 'redis',
            'session.connection' => config('web_auth.connection'),
            'session.cookie' => 'beaulab_'.$actor->value.'_session',
            'session.path' => '/api/v1/'.$actor->value,
            'session.domain' => null,
            'session.http_only' => true,
            'session.secure' => ! app()->environment(['local', 'testing']),
            'session.same_site' => 'lax',
            'session.expire_on_close' => ! $policy['persistent'],
            'session.lifetime' => max(1, $policy['idle_minutes']),
            'sanctum.guard' => [$actor->guard()],
        ]);
        app(SessionManager::class)->forgetDrivers();
        app()->forgetInstance('session.store');
        $request->attributes->set(ActorAuthentication::ACTOR_ATTRIBUTE, $actor);

        try {
            $response = (new Pipeline(app()))->send($request)->through([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ValidateWebCsrfToken::class,
                ValidateWebSession::class,
            ])->then($next);
            $cookieName = 'beaulab_'.$actor->value.'_session';
            $issuesCookie = $request->is('api/v1/*/auth/csrf', 'api/v1/*/auth/login', 'api/v1/*/auth/logout')
                && $request->cookie($cookieName) !== $request->session()->getId();
            foreach ($response->headers->getCookies() as $cookie) {
                if ($cookie->getName() !== $cookieName) {
                    continue;
                }

                // 늦게 도착한 일반 응답이 새 로그인 쿠키를 이전 세션으로 되돌리지 않는다.
                if (! $issuesCookie) {
                    $response->headers->removeCookie($cookieName, $cookie->getPath(), $cookie->getDomain());
                } elseif ($policy['persistent'] && ($expiresAt = $request->session()->get(ActorAuthentication::SESSION_KEY.'.expires_at'))) {
                    // 쿠키는 절대 만료까지만 유지하고 유휴 만료는 서버에서 계속 검사한다.
                    $response->headers->setCookie($cookie->withExpires((int) $expiresAt));
                }
            }
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('Vary', 'Origin, Cookie', false);

            return $response;
        } finally {
            config(['session' => $previousSession, 'sanctum.guard' => $previousGuards]);
            app(SessionManager::class)->forgetDrivers();
        }
    }
}
