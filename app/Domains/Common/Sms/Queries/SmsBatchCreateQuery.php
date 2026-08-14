<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Queries;

use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Common\Sms\Models\SmsDelivery;

final class SmsBatchCreateQuery
{
    public function findForUpdate(string $idempotencyKey): ?SmsBatch
    {
        return SmsBatch::query()
            ->where('idempotency_key', $idempotencyKey)
            ->lockForUpdate()
            ->first();
    }

    public function createBatch(array $attributes): SmsBatch
    {
        return SmsBatch::query()->create($attributes);
    }

    public function createDelivery(SmsBatch $batch, array $attributes): SmsDelivery
    {
        return $batch->deliveries()->create($attributes);
    }
}
