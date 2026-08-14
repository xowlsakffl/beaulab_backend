<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Data;

final readonly class SmsSendResult
{
    public function __construct(
        public string $provider,
        public ?string $providerMessageId,
    ) {}
}
