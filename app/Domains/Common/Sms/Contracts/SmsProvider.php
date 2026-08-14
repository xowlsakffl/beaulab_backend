<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Contracts;

use App\Domains\Common\Sms\Data\SmsSendResult;

interface SmsProvider
{
    public function name(): string;

    public function send(
        string $phone,
        string $message,
        string $messageType,
        string $idempotencyKey,
    ): SmsSendResult;
}
