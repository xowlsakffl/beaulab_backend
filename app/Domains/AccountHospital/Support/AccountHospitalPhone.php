<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use App\Domains\Common\Sms\Support\SmsMessage;

final class AccountHospitalPhone
{
    public static function normalize(string $phone): string
    {
        return SmsMessage::normalizePhone($phone);
    }

    public static function isValid(string $phone): bool
    {
        return SmsMessage::isSendablePhone($phone);
    }

    public static function format(string $phone): string
    {
        $normalized = self::normalize($phone);

        if (strlen($normalized) === 11) {
            return substr($normalized, 0, 3).'-'.substr($normalized, 3, 4).'-'.substr($normalized, 7, 4);
        }

        if (strlen($normalized) === 10) {
            return substr($normalized, 0, 3).'-'.substr($normalized, 3, 3).'-'.substr($normalized, 6, 4);
        }

        return $normalized;
    }
}
