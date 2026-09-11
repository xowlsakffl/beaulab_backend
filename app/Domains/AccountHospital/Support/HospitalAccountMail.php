<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

final class HospitalAccountMail
{
    public static function assertConfigured(): void
    {
        if (self::usesUnsafeTransport((string) config('mail.default'))) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이메일 발송 설정을 확인해 주세요.');
        }
    }

    private static function usesUnsafeTransport(string $name, array $visited = []): bool
    {
        if (in_array($name, $visited, true)) {
            return true;
        }
        $config = config("mail.mailers.{$name}", []);
        $transport = $config['transport'] ?? null;
        if ($transport === null || $transport === 'log' || ($transport === 'array' && app()->environment('production'))) {
            return true;
        }
        foreach ($config['mailers'] ?? [] as $child) {
            if (self::usesUnsafeTransport($child, [...$visited, $name])) {
                return true;
            }
        }

        return false;
    }

    public static function queue(string $email, Mailable $mail): void
    {
        self::assertConfigured();
        Mail::to($email)->queue($mail
            ->onConnection((string) config('hospital_account_invitation.mail.connection', 'redis'))
            ->onQueue((string) config('hospital_account_invitation.mail.queue', 'mail'))
            ->afterCommit());
    }
}
