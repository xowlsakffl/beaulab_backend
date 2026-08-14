<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Support;

final class SmsMessage
{
    public static function byteLength(string $message): int
    {
        return collect(mb_str_split($message))
            ->sum(static fn (string $character): int => strlen($character) === 1 ? 1 : 2);
    }

    public static function type(string $message): string
    {
        return self::byteLength($message) <= (int) config('sms.sms_max_bytes', 90)
            ? 'SMS'
            : 'LMS';
    }

    public static function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public static function isSendablePhone(?string $phone): bool
    {
        $normalized = self::normalizePhone($phone);

        return preg_match('/^01\d{8,9}$/', $normalized) === 1;
    }
}
