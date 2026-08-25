<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use Illuminate\Support\Str;

final class HospitalAccountIdentityVerificationToken
{
    public static function make(): string
    {
        return Str::random(64);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
