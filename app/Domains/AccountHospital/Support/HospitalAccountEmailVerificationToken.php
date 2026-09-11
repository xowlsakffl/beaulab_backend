<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

final class HospitalAccountEmailVerificationToken
{
    public static function make(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
