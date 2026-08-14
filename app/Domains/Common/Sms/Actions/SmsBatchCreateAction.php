<?php

declare(strict_types=1);

namespace App\Domains\Common\Sms\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Sms\Data\SmsBatchCreateResult;
use App\Domains\Common\Sms\Data\SmsDeliveryData;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\Common\Sms\Models\SmsDelivery;
use App\Domains\Common\Sms\Queries\SmsBatchCreateQuery;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JsonException;

final class SmsBatchCreateAction
{
    public function __construct(
        private readonly SmsBatchCreateQuery $query,
        private readonly SmsPendingDispatchAction $dispatchPending,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @param  Closure(): list<SmsDeliveryData>  $resolveDeliveries
     *
     * @throws JsonException
     */
    public function execute(
        string $purpose,
        string $idempotencyKey,
        ?string $messageTemplate,
        array $metadata,
        int $targetCount,
        ?Model $actor,
        Closure $resolveDeliveries,
    ): SmsBatchCreateResult {
        $requestHash = $this->requestHash($purpose, $messageTemplate, $metadata, $targetCount);
        $created = false;

        $batch = DB::transaction(function () use (
            $purpose,
            $idempotencyKey,
            $requestHash,
            $messageTemplate,
            $metadata,
            $targetCount,
            $actor,
            $resolveDeliveries,
            &$created,
        ): SmsBatch {
            $existing = $this->query->findForUpdate($idempotencyKey);
            if ($existing instanceof SmsBatch) {
                $this->assertMatchingRetry($existing, $purpose, $requestHash);

                return $existing;
            }

            $deliveries = collect($resolveDeliveries())->values();
            $this->assertUniqueDeliveryKeys(
                $deliveries->map(static fn (SmsDeliveryData $delivery): string => $delivery->deduplicationKey)->all(),
            );

            $sendableCount = $deliveries
                ->where('status', SmsDelivery::STATUS_PENDING)
                ->count();
            $skippedCount = $deliveries
                ->where('status', SmsDelivery::STATUS_SKIPPED)
                ->count();

            $batch = $this->query->createBatch([
                'idempotency_key' => $idempotencyKey,
                'purpose' => $purpose,
                'request_hash' => $requestHash,
                'message_template' => $messageTemplate,
                'metadata' => $metadata,
                'target_count' => max(0, $targetCount),
                'recipient_count' => $deliveries->count(),
                'sent_count' => 0,
                'failed_count' => 0,
                'skipped_count' => $skippedCount,
                'status' => $sendableCount > 0
                    ? SmsBatch::STATUS_PENDING
                    : SmsBatch::STATUS_FAILED,
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor?->getKey(),
                'completed_at' => $sendableCount > 0 ? null : now(),
            ]);

            $deliveries->each(
                fn (SmsDeliveryData $delivery) => $this->query->createDelivery($batch, $delivery->toArray()),
            );

            $created = true;

            return $batch;
        });

        $deliveryIds = $batch->deliveries()
            ->where('status', SmsDelivery::STATUS_PENDING)
            ->pluck('id');
        $queuedCount = $this->dispatchPending->execute(
            $deliveryIds->map(static fn ($id): int => (int) $id)->all(),
            $deliveryIds->count(),
        );

        if ($queuedCount > 0) {
            $batch->forceFill(['queued_at' => now()])->save();
        }

        return new SmsBatchCreateResult($batch, ! $created);
    }

    /**
     * @param  array<string, mixed>  $metadata
     *
     * @throws JsonException
     */
    private function requestHash(
        string $purpose,
        ?string $messageTemplate,
        array $metadata,
        int $targetCount,
    ): string {
        return hash('sha256', json_encode([
            'purpose' => $purpose,
            'message_template' => $messageTemplate,
            'metadata' => $metadata,
            'target_count' => $targetCount,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function assertMatchingRetry(SmsBatch $batch, string $purpose, string $requestHash): void
    {
        if ($batch->purpose === $purpose && hash_equals((string) $batch->request_hash, $requestHash)) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '동일한 중복 처리 방지 키가 다른 문자 발송 요청에 이미 사용되었습니다.',
        );
    }

    /**
     * @param  list<mixed>  $keys
     */
    private function assertUniqueDeliveryKeys(array $keys): void
    {
        $normalized = collect($keys)
            ->filter(static fn ($key): bool => is_string($key) && $key !== '')
            ->values();

        if ($normalized->count() === count($keys) && $normalized->unique()->count() === $normalized->count()) {
            return;
        }

        throw new CustomException(
            ErrorCode::INVALID_REQUEST,
            '문자 발송 대상의 중복 방지 키가 올바르지 않습니다.',
        );
    }
}
