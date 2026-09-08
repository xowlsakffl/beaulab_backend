<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Actions;

use App\Domains\Common\Sms\Contracts\SmsProvider;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Common\Sms\Models\SmsDelivery;
use Illuminate\Support\Facades\DB;

final class SmsDeliveryRecoveryAction
{
    public function __construct(
        private readonly SmsProvider $provider,
        private readonly SmsBatchStatusSyncAction $syncBatch,
    ) {}

    public function execute(int $limit = 500): void
    {
        $cutoff = now()->subMinutes(5);
        $ids = SmsDelivery::query()
            ->where('status', SmsDelivery::STATUS_PROCESSING)
            ->where('attempted_at', '<', $cutoff)
            ->orderBy('id')->limit($limit)->pluck('id');

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, $cutoff): void {
                $delivery = SmsDelivery::query()->lockForUpdate()->find($id);
                if (! $delivery || $delivery->status !== SmsDelivery::STATUS_PROCESSING
                    || $delivery->attempted_at === null || $delivery->attempted_at->gte($cutoff)) {
                    return;
                }

                $retryable = $delivery->provider === $this->provider->name()
                    && $this->provider->supportsIdempotentRetries()
                    && (int) $delivery->attempt_count < SendSmsDeliveryAction::MAX_ATTEMPTS;

                $delivery->forceFill([
                    'status' => $retryable ? SmsDelivery::STATUS_PENDING : SmsDelivery::STATUS_FAILED,
                    'queued_at' => null,
                    'failed_at' => $retryable ? null : now(),
                    'error_message' => 'Worker lease expired; verify the provider result before a non-idempotent retry.',
                ])->save();
            });
        }

        SmsBatch::query()->whereNull('completed_at')->orderBy('id')->limit($limit)->pluck('id')
            ->each(fn ($id) => $this->syncBatch->execute((int) $id));
    }
}
