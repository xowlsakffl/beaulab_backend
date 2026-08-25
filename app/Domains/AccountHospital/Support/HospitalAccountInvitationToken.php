<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use Illuminate\Support\Str;

final class HospitalAccountInvitationToken
{
    public static function make(): string
    {
        return Str::random(64);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function url(string $token): string
    {
        $baseUrl = rtrim((string) config('hospital_account_invitation.url'), '?&');
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query(['token' => $token]);
    }
}
