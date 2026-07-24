<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $actor = $this->actor($request);
            $identifier = $this->loginIdentifier($request);

            return [
                Limit::perMinute(6)->by("auth-login:{$actor}:{$identifier}:{$request->ip()}"),
                Limit::perMinute(30)->by("auth-login:{$actor}:{$request->ip()}"),
            ];
        });

        RateLimiter::for('password-reset-link', function (Request $request) {
            $actor = $this->actor($request);
            $email = $this->email($request);
            $emailKey = $email !== '' ? $email : $request->ip();

            return [
                Limit::perMinute(3)->by("password-reset-link:{$actor}:{$emailKey}"),
                Limit::perMinute(10)->by("password-reset-link:{$actor}:{$request->ip()}"),
            ];
        });

        RateLimiter::for('password-reset-verify', function (Request $request) {
            $actor = $this->actor($request);
            $email = $this->email($request);
            $key = $email !== '' ? "{$actor}:{$email}" : "{$actor}:{$request->ip()}";

            return Limit::perMinute(20)->by("password-reset-verify:{$key}");
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            $actor = $this->actor($request);
            $email = $this->email($request);
            $emailKey = $email !== '' ? $email : $request->ip();

            return [
                Limit::perMinute(6)->by("password-reset-submit:{$actor}:{$emailKey}"),
                Limit::perMinute(20)->by("password-reset-submit:{$actor}:{$request->ip()}"),
            ];
        });

        RateLimiter::for('password-update', function (Request $request) {
            $actor = $this->actor($request);
            $userKey = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return [
                Limit::perMinute(6)->by("password-update:{$actor}:{$userKey}"),
                Limit::perMinute(20)->by("password-update:{$actor}:{$request->ip()}"),
            ];
        });

        RateLimiter::for('content-report-create', function (Request $request) {
            $userKey = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return [
                Limit::perMinute(10)->by("content-report-create:user:{$userKey}"),
                Limit::perMinute(60)->by("content-report-create:ip:{$request->ip()}"),
            ];
        });
    }

    private function actor(Request $request): string
    {
        return (string) ($request->segment(3) ?: 'unknown');
    }

    private function loginIdentifier(Request $request): string
    {
        $identifier = $request->input('email', $request->input('nickname', ''));

        return mb_strtolower(trim((string) $identifier)) ?: 'unknown';
    }

    private function email(Request $request): string
    {
        return mb_strtolower(trim((string) $request->input('email', '')));
    }
}
