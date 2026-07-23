<?php

namespace App\Domains\Common\PasswordReset\Support;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;

final class PasswordResetActor
{
    public const string USER = 'user';

    public const string HOSPITAL = 'hospital';

    public const string BEAUTY = 'beauty';

    public const string STAFF = 'staff';

    public static function broker(string $actor): string
    {
        return match ($actor) {
            self::USER => 'users',
            self::HOSPITAL => 'hospitals',
            self::BEAUTY => 'beauties',
            self::STAFF => 'staff',
        };
    }

    public static function label(string $actor): string
    {
        return match ($actor) {
            self::USER => '일반 회원',
            self::HOSPITAL => '병의원 계정',
            self::BEAUTY => '뷰티 계정',
            self::STAFF => '관리자 계정',
        };
    }

    public static function resetUrl(string $actor, string $email, string $token): string
    {
        $baseUrl = rtrim((string) config("password_reset.urls.{$actor}", config('app.url').'/password/reset'), '?&');
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query([
            'actor' => $actor,
            'email' => $email,
            'token' => $token,
        ]);
    }

    public static function expireMinutes(string $actor): int
    {
        return (int) config('auth.passwords.'.self::broker($actor).'.expire', 60);
    }

    public static function throttleSeconds(string $actor): int
    {
        return (int) config('auth.passwords.'.self::broker($actor).'.throttle', 60);
    }

    public static function findAccountByEmail(string $actor, string $email): ?Model
    {
        $modelClass = match ($actor) {
            self::USER => AccountUser::class,
            self::HOSPITAL => AccountHospital::class,
            self::BEAUTY => AccountBeauty::class,
            self::STAFF => AccountStaff::class,
        };

        $account = $modelClass::query()
            ->where('email', $email)
            ->first();

        return $account instanceof Model ? $account : null;
    }

    public static function canResetPassword(Model $account): bool
    {
        if (method_exists($account, 'isActive')) {
            return (bool) $account->isActive();
        }

        return true;
    }
}
