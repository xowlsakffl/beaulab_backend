<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Str;

final class HospitalAccountPasswordResetToken
{
    public static function make(): string
    {
        return Str::random(64);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function credentialHash(AccountHospital $account): string
    {
        return hash('sha256', implode('|', [
            $account->getAuthPassword(),
            AccountHospitalPhone::normalize((string) $account->verifiedPhone()),
            $account->phone_verified_at?->toISOString() ?? '',
            (string) $account->hospital_id,
        ]));
    }

    public static function url(string $token): string
    {
        $url = (string) config('password_reset.hospital.url');

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query(['token' => $token]);
    }
}
