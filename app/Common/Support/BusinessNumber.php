<?php

declare(strict_types=1);

namespace App\Common\Support;

final class BusinessNumber
{
    public const string VALIDATION_RULE = 'regex:/^\d{10}$/';

    public static function normalize(string $value): string
    {
        return str_replace('-', '', trim($value));
    }
}
