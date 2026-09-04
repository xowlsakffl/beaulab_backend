<?php

namespace App\Common\Auth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

final class ActorAuthentication
{
    public const string ACTOR_ATTRIBUTE = 'web_auth_actor';

    public const string SESSION_KEY = 'web_auth';

    public function __construct(private readonly Request $request) {}

    public function login(User $account, AuthActor $actor, ?string $deviceName = null): array
    {
        if ($this->request->attributes->get(self::ACTOR_ATTRIBUTE) !== $actor) {
            if ($actor !== AuthActor::USER) {
                throw new AuthenticationException('웹 세션 인증이 필요합니다.');
            }

            return ['token' => $account->createToken($deviceName ?: 'user-app', ['actor:user'])->plainTextToken];
        }

        $this->revokeCurrentLogin();
        $this->request->session()->invalidate();
        Auth::guard($actor->guard())->login($account);
        $this->request->session()->regenerateToken();
        $policy = $actor->policy();
        $now = now()->timestamp;

        $this->request->session()->put(self::SESSION_KEY, [
            'actor' => $actor->value,
            'login_id' => Str::random(64),
            'password' => $this->passwordFingerprint($account),
            'expires_at' => $now + max(1, $policy['absolute_minutes']) * 60,
            'idle_expires_at' => $now + max(1, $policy['idle_minutes']) * 60,
        ]);

        return ['session' => $this->metadata()];
    }

    public function logout(?Authenticatable $account): void
    {
        $actor = $this->request->attributes->get(self::ACTOR_ATTRIBUTE);
        if ($actor instanceof AuthActor) {
            $this->revokeCurrentLogin();
            Auth::guard($actor->guard())->logout();
            $this->request->session()->invalidate();
            $this->request->session()->regenerateToken();

            return;
        }

        $token = $account?->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    public function metadata(): ?array
    {
        $state = $this->request->hasSession() ? $this->request->session()->get(self::SESSION_KEY) : null;
        if (! is_array($state)) {
            return null;
        }

        return [
            'expires_at' => $state['expires_at'],
            'idle_expires_at' => $state['idle_expires_at'],
        ];
    }

    public function passwordFingerprint(User $account): string
    {
        return hash_hmac('sha256', $account->getAuthPassword(), config('app.key'));
    }

    public function isRevoked(string $loginId): bool
    {
        return Cache::store(config('web_auth.revocation_store'))->has('web-auth:revoked:'.$loginId);
    }

    private function revokeCurrentLogin(): void
    {
        $state = $this->request->session()->get(self::SESSION_KEY);
        if (! is_array($state) || empty($state['login_id'])) {
            return;
        }

        Cache::store(config('web_auth.revocation_store'))->put(
            'web-auth:revoked:'.$state['login_id'], true,
            max(60, (int) $state['expires_at'] - now()->timestamp),
        );
    }
}
