<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Actions;

use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Common\Sms\Models\SmsDelivery;
use Illuminate\Support\Facades\DB;

final class SmsBatchStatusSyncAction
{
    public function execute(int $batchId): void
    {
        DB::transaction(function () use ($batchId): void {
            $batch = SmsBatch::query()
                ->lockForUpdate()
                ->find($batchId);

            if (! $batch instanceof SmsBatch) {
                return;
            }

            $counts = SmsDelivery::query()
                ->where('sms_batch_id', $batch->id)
                ->selectRaw('count(*) as recipient_count')
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as sent_count', [SmsDelivery::STATUS_SENT])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as failed_count', [SmsDelivery::STATUS_FAILED])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as skipped_count', [SmsDelivery::STATUS_SKIPPED])
                ->selectRaw('sum(case when status in (?, ?) then 1 else 0 end) as open_count', [
                    SmsDelivery::STATUS_PENDING,
                    SmsDelivery::STATUS_PROCESSING,
                ])
                ->first();

            $recipientCount = (int) ($counts?->recipient_count ?? 0);
            $sentCount = (int) ($counts?->sent_count ?? 0);
            $failedCount = (int) ($counts?->failed_count ?? 0);
            $skippedCount = (int) ($counts?->skipped_count ?? 0);
            $openCount = (int) ($counts?->open_count ?? 0);

            $status = match (true) {
                $openCount > 0 && ($sentCount + $failedCount) > 0 => SmsBatch::STATUS_PROCESSING,
                $openCount > 0 => SmsBatch::STATUS_PENDING,
                $recipientCount > 0 && $sentCount === $recipientCount => SmsBatch::STATUS_SENT,
                $sentCount > 0 => SmsBatch::STATUS_PARTIAL_FAILED,
                default => SmsBatch::STATUS_FAILED,
            };

            $batch->forceFill([
                'recipient_count' => $recipientCount,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'skipped_count' => $skippedCount,
                'status' => $status,
                'completed_at' => $openCount === 0 ? now() : null,
            ])->save();
        });
    }
}
