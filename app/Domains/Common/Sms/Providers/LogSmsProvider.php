<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Providers;

use App\Domains\Common\Sms\Contracts\SmsProvider;
use App\Domains\Common\Sms\Data\SmsSendResult;
use Illuminate\Support\Facades\Log;

final class LogSmsProvider implements SmsProvider
{
    public function supportsIdempotentRetries(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'LOG';
    }

    public function send(
        string $phone,
        string $message,
        string $messageType,
        string $idempotencyKey,
    ): SmsSendResult {
        Log::info('문자 발송 로그 Provider 처리', [
            'phone' => $phone,
            'message_type' => $messageType,
            'message' => $message,
            'idempotency_key' => $idempotencyKey,
        ]);

        return new SmsSendResult(
            provider: $this->name(),
            providerMessageId: 'log-'.$idempotencyKey,
        );
    }
}
