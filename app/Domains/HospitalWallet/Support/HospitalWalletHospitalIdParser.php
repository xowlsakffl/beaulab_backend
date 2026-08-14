<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Support;

final class HospitalWalletHospitalIdParser
{
    public static function fromKeyword(string $keyword, bool $allowPlainNumber = false): ?int
    {
        $keyword = trim($keyword);
        if ($allowPlainNumber && ctype_digit($keyword)) {
            return (int) $keyword;
        }

        if (preg_match('/^HID[-_ ]?(\d+)$/i', $keyword, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
