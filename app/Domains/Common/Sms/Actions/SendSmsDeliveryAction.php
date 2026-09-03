<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Actions;

use App\Domains\Common\Sms\Contracts\SmsProvider;
use App\Domains\Common\Sms\Models\SmsDelivery;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SendSmsDeliveryAction
{
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

        try {
            $result = $this->provider->send(
                (string) $delivery->phone_normalized,
                (string) ($delivery->encrypted_message_body ?? $delivery->message_body),
                (string) $delivery->message_type,
                "sms-delivery:{$delivery->id}",
            );

            $delivery->forceFill([
                'status' => SmsDelivery::STATUS_SENT,
                'provider' => $result->provider,
                'provider_message_id' => $result->providerMessageId,
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ])->save();

            $this->syncBatchStatus->execute((int) $delivery->sms_batch_id);
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => SmsDelivery::STATUS_PENDING,
                'provider' => $this->provider->name(),
                'error_message' => mb_strimwidth($exception->getMessage(), 0, 2000, '...'),
            ])->save();

            $this->syncBatchStatus->execute((int) $delivery->sms_batch_id);

            throw $exception;
        }
    }

    public function markFailed(int $deliveryId, Throwable $exception): void
    {
        $delivery = SmsDelivery::query()->find($deliveryId);
        if (! $delivery instanceof SmsDelivery
            || in_array($delivery->status, [SmsDelivery::STATUS_SENT, SmsDelivery::STATUS_SKIPPED], true)) {
            return;
        }

        $delivery->forceFill([
            'status' => SmsDelivery::STATUS_FAILED,
            'provider' => $delivery->provider ?: $this->provider->name(),
            'failed_at' => now(),
            'error_message' => mb_strimwidth($exception->getMessage(), 0, 2000, '...'),
        ])->save();

        $this->syncBatchStatus->execute((int) $delivery->sms_batch_id);
    }
}
