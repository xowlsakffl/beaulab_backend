<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Actions;

use App\Domains\Common\Sms\Contracts\SmsProvider;
use App\Domains\Common\Sms\Models\SmsDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendSmsDeliveryAction
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly SmsProvider $provider,
        private readonly SmsBatchStatusSyncAction $syncBatchStatus,
    ) {}

    public function execute(int $deliveryId): void
    {
        $delivery = DB::transaction(function () use ($deliveryId): ?SmsDelivery {
            $delivery = SmsDelivery::query()
                ->lockForUpdate()
                ->find($deliveryId);

            if (! $delivery instanceof SmsDelivery || $delivery->status !== SmsDelivery::STATUS_PENDING) {
                return null;
            }

            if ((int) $delivery->attempt_count >= self::MAX_ATTEMPTS) {
                $delivery->forceFill(['status' => SmsDelivery::STATUS_FAILED, 'failed_at' => now()])->save();

                return $delivery;
            }

            $delivery->forceFill([
                'status' => SmsDelivery::STATUS_PROCESSING,
                'provider' => $this->provider->name(),
                'attempt_count' => (int) $delivery->attempt_count + 1,
                'attempted_at' => now(),
                'failed_at' => null,
            ])->save();

            return $delivery;
        });

        if (! $delivery instanceof SmsDelivery) {
            return;
        }

        if ($delivery->status === SmsDelivery::STATUS_FAILED) {
            $this->syncBatchSafely((int) $delivery->sms_batch_id);

            return;
        }

        try {
            $result = $this->provider->send(
                (string) $delivery->phone_normalized,
                (string) ($delivery->encrypted_message_body ?? $delivery->message_body),
                (string) $delivery->message_type,
                "sms-delivery:{$delivery->id}",
            );

            SmsDelivery::query()->whereKey($delivery->id)
                ->where('status', SmsDelivery::STATUS_PROCESSING)
                ->where('attempt_count', $delivery->attempt_count)->update([
                    'status' => SmsDelivery::STATUS_SENT,
                    'provider' => $result->provider,
                    'provider_message_id' => $result->providerMessageId,
                    'sent_at' => now(),
                    'failed_at' => null,
                    'error_message' => null,
                ]);

        } catch (Throwable $exception) {
            $retryable = $this->provider->supportsIdempotentRetries()
                && (int) $delivery->attempt_count < self::MAX_ATTEMPTS;
            SmsDelivery::query()->whereKey($delivery->id)
                ->where('status', SmsDelivery::STATUS_PROCESSING)
                ->where('attempt_count', $delivery->attempt_count)->update([
                    'status' => $retryable ? SmsDelivery::STATUS_PENDING : SmsDelivery::STATUS_FAILED,
                    'failed_at' => $retryable ? null : now(),
                    'provider' => $this->provider->name(),
                    'error_message' => mb_strimwidth($exception->getMessage(), 0, 2000, '...'),
                ]);

            $this->syncBatchSafely((int) $delivery->sms_batch_id);

            throw $exception;
        }

        $this->syncBatchSafely((int) $delivery->sms_batch_id);
    }

    private function syncBatchSafely(int $batchId): void
    {
        try {
            $this->syncBatchStatus->execute($batchId);
        } catch (Throwable $exception) {
            Log::warning('SMS batch summary update failed; scheduled recovery will retry.', [
                'batch_id' => $batchId,
                'exception' => $exception::class,
            ]);
        }
    }

    public function markFailed(int $deliveryId, Throwable $exception): void
    {
        // A failed queue copy must not overwrite a newer delivery attempt.
        Log::warning('SMS queue job exhausted; delivery recovery will inspect its persisted state.', [
            'delivery_id' => $deliveryId,
            'exception' => $exception::class,
        ]);
    }
}
