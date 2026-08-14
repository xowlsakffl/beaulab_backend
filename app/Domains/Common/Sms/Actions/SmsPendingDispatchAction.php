<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Actions;

use App\Domains\Common\Sms\Jobs\SendSmsDeliveryJob;
use App\Domains\Common\Sms\Models\SmsDelivery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SmsPendingDispatchAction
{
    /**
     * @param  list<int>|null  $deliveryIds
     */
    public function execute(
        ?array $deliveryIds = null,
        int $limit = 100,
        ?int $staleMinutes = null,
    ): int {
        $staleBefore = $staleMinutes !== null && $staleMinutes > 0
            ? now()->subMinutes($staleMinutes)
            : null;
        $ids = SmsDelivery::query()
            ->where('status', SmsDelivery::STATUS_PENDING)
            ->when(
                $deliveryIds !== null,
                fn (Builder $query) => $query->whereIn('id', $deliveryIds),
            )
            ->where(function (Builder $query) use ($staleBefore): void {
                $this->applyQueueClaimCondition($query, $staleBefore);
            })
            ->orderBy('id')
            ->limit(max(1, min($limit, 1000)))
            ->pluck('id');

        $queuedCount = 0;

        foreach ($ids as $id) {
            $claimed = SmsDelivery::query()
                ->whereKey($id)
                ->where('status', SmsDelivery::STATUS_PENDING)
                ->where(function (Builder $query) use ($staleBefore): void {
                    $this->applyQueueClaimCondition($query, $staleBefore);
                })
                ->update(['queued_at' => now()]);

            if ($claimed !== 1) {
                continue;
            }

            try {
                SendSmsDeliveryJob::dispatch((int) $id);
                $queuedCount++;
            } catch (Throwable $exception) {
                SmsDelivery::query()
                    ->whereKey($id)
                    ->where('status', SmsDelivery::STATUS_PENDING)
                    ->update(['queued_at' => null]);

                Log::warning('문자 큐 등록 실패', [
                    'delivery_id' => (int) $id,
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $queuedCount;
    }

    private function applyQueueClaimCondition(Builder $query, ?CarbonInterface $staleBefore): void
    {
        $query->whereNull('queued_at');

        if ($staleBefore !== null) {
            $query->orWhere('queued_at', '<', $staleBefore);
        }
    }
}
