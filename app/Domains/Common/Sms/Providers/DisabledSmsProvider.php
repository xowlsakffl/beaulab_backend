<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Providers;

use App\Domains\Common\Sms\Contracts\SmsProvider;
use App\Domains\Common\Sms\Data\SmsSendResult;
use RuntimeException;

final class DisabledSmsProvider implements SmsProvider
{
    public function supportsIdempotentRetries(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'DISABLED';
    }

    public function send(
        string $phone,
        string $message,
        string $messageType,
        string $idempotencyKey,
    ): SmsSendResult {
        throw new RuntimeException('문자 발송 Provider가 설정되지 않았습니다.');
    }
}
