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

        RateLimiter::for('staff-sms-send', function (Request $request) {
            $staffKey = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return [
                Limit::perMinute(10)->by("staff-sms-send:staff:{$staffKey}"),
                Limit::perMinute(30)->by("staff-sms-send:ip:{$request->ip()}"),
            ];
        });

        RateLimiter::for('staff-hospital-account-invitation-send', function (Request $request) {
            $staffKey = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return [
                Limit::perMinute(10)->by("hospital-account-invitation-send:staff:{$staffKey}"),
                Limit::perMinute(30)->by("hospital-account-invitation-send:ip:{$request->ip()}"),
            ];
        });

        RateLimiter::for('hospital-account-invitation-verify', function (Request $request) {
            $tokenHash = hash('sha256', (string) $request->route('token'));

            return [
                Limit::perMinute(20)->by("hospital-account-invitation-verify:token:{$tokenHash}"),
                Limit::perMinute(60)->by("hospital-account-invitation-verify:ip:{$request->ip()}"),
            ];
        });

        RateLimiter::for('hospital-account-invitation-complete', function (Request $request) {
            $tokenHash = hash('sha256', (string) $request->route('token'));

            return [
                Limit::perMinute(5)->by("hospital-account-invitation-complete:token:{$tokenHash}"),
                Limit::perMinute(15)->by("hospital-account-invitation-complete:ip:{$request->ip()}"),
            ];
        });

        RateLimiter::for('hospital-account-phone-verification-send', function (Request $request) {
            $tokenHash = hash('sha256', (string) $request->route('token'));
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone')) ?: 'unknown';

            return [
                Limit::perMinute(2)->by("hospital-account-phone-send:token:{$tokenHash}:{$phone}"),
                Limit::perHour(5)->by("hospital-account-phone-send-hour:token:{$tokenHash}:{$phone}"),
                Limit::perHour(20)->by("hospital-account-phone-send-hour:ip:{$request->ip()}"),
            ];
        });

        RateLimiter::for('hospital-account-phone-verification-verify', function (Request $request) {
            $tokenHash = hash('sha256', (string) $request->route('token'));
            $verificationId = (string) $request->route('phoneVerification');

            return [
                Limit::perMinute(10)->by("hospital-account-phone-verify:token:{$tokenHash}:{$verificationId}"),
                Limit::perMinute(30)->by("hospital-account-phone-verify:ip:{$request->ip()}"),
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
