<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use Illuminate\Support\Facades\Hash;

final class HospitalAccountEmailVerificationCode
{
    public static function make(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function hash(string $code): string
    {
        return Hash::make($code);
    }

    public static function verify(string $code, string $hash): bool
    {
        return Hash::check($code, $hash);
    }
}
