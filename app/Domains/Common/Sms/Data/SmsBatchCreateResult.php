<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Data;

use App\Domains\Common\Sms\Models\SmsBatch;

final readonly class SmsBatchCreateResult
{
    public function __construct(
        public SmsBatch $batch,
        public bool $replayed,
    ) {}
}
