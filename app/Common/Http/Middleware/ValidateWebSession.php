<?php

namespace App\Common\Http\Middleware;

use App\Common\Auth\ActorAuthentication;
use App\Common\Auth\AuthActor;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class ValidateWebSession
{
    public function __construct(private readonly ActorAuthentication $authentication) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var AuthActor $actor */
        $actor = $request->attributes->get(ActorAuthentication::ACTOR_ATTRIBUTE);
        $account = Auth::guard($actor->guard())->user();
        $state = $request->session()->get(ActorAuthentication::SESSION_KEY);
        $now = now()->timestamp;

        if ($account) {
            $valid = is_array($state)
                && ($state['actor'] ?? null) === $actor->value
                && ($state['expires_at'] ?? 0) > $now
                && ($state['idle_expires_at'] ?? 0) > $now
                && $account->isActive()
                && hash_equals($state['password'] ?? '', $this->authentication->passwordFingerprint($account))
                && ! $this->authentication->isRevoked($state['login_id'] ?? '');

            if (! $valid) {
                $this->authentication->logout($account);
                if (! $request->is('api/v1/*/auth/*')) {
                    throw new AuthenticationException;
                }
            } elseif (! $request->is(
                'api/v1/*/auth/csrf', 'api/v1/*/auth/session',
                'api/v1/staff/navigation-badges', 'api/v1/user/notifications/unread-count',
            )) {
                $state['idle_expires_at'] = min($state['expires_at'], $now + max(1, $actor->policy()['idle_minutes']) * 60);
                $request->session()->put(ActorAuthentication::SESSION_KEY, $state);
            }
        }

        $response = $next($request);
        $metadata = $this->authentication->metadata();
        if ($metadata) {
            $response->headers->set('X-Session-Expires-At', (string) $metadata['expires_at']);
            $response->headers->set('X-Session-Idle-Expires-At', (string) $metadata['idle_expires_at']);
        }

        return $response;
    }
}
