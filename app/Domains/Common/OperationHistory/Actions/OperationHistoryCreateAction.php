<?php

namespace App\Domains\Common\OperationHistory\Actions;

use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Queries\OperationHistoryCreateQuery;
use App\Domains\Common\OperationHistory\Support\OperationHistoryActorRegistry;
use App\Domains\Common\OperationHistory\Support\OperationHistoryTargetRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * OperationHistoryCreateAction 역할 정의.
 * 상세 화면에 표시할 운영/시스템 액션 히스토리를 생성한다.
 */
final class OperationHistoryCreateAction
{
    public function __construct(
        private readonly OperationHistoryCreateQuery $query,
    ) {}

    public function execute(
        Model $target,
        string $action,
        ?Model $actor = null,
        ?string $reason = null,
        array $metadata = [],
        ?string $actorKind = null,
        ?string $batchUuid = null,
        array $changes = [],
    ): OperationHistory {
        OperationHistoryTargetRegistry::assertSupported($target);

        return DB::transaction(function () use ($target, $action, $actor, $actorKind, $batchUuid, $reason, $metadata, $changes): OperationHistory {
            $history = $this->query->create([
                'target_type' => $target::class,
                'target_id' => (int) $target->getKey(),
                'actor_type' => $actor?->getMorphClass(),
                'actor_id' => $actor ? (int) $actor->getKey() : null,
                'actor_kind' => $actorKind ?? OperationHistoryActorRegistry::kindForActor($actor),
                'action' => $action,
                'batch_uuid' => $batchUuid,
                'reason' => $this->normalizeReason($reason),
                'metadata' => $metadata === [] ? null : $metadata,
            ]);

            $normalizedChanges = $this->normalizeChanges($changes);
            if ($normalizedChanges !== []) {
                $history->changes()->createMany($normalizedChanges);
            }

            return $history->load('changes');
        });
    }

    /**
     * @param array<int, array<string, mixed>> $changes
     * @return array<int, array<string, mixed>>
     */
    private function normalizeChanges(array $changes): array
    {
        $out = [];
        foreach ($changes as $index => $change) {
            $fieldKey = trim((string) ($change['field_key'] ?? $change['field'] ?? ''));
            if ($fieldKey === '') {
                continue;
            }

            $before = array_key_exists('before_value', $change) ? $change['before_value'] : ($change['before'] ?? null);
            $after = array_key_exists('after_value', $change) ? $change['after_value'] : ($change['after'] ?? null);
            $beforeDisplay = $change['before_display'] ?? $this->normalizeDisplayValue($before);
            $afterDisplay = $change['after_display'] ?? $this->normalizeDisplayValue($after);

            $out[] = [
                'field_key' => $fieldKey,
                'field_label' => trim((string) ($change['field_label'] ?? $change['label'] ?? $fieldKey)),
                'before_value' => $this->normalizeJsonValue($before),
                'after_value' => $this->normalizeJsonValue($after),
                'before_display' => $beforeDisplay,
                'after_display' => $afterDisplay,
                'sort_order' => (int) ($change['sort_order'] ?? $index),
            ];
        }

        return $out;
    }

    private function normalizeJsonValue(mixed $value): mixed
    {
        if ($value === null || is_bool($value) || is_numeric($value) || is_string($value) || is_array($value)) {
            return $value;
        }

        return $this->normalizeDisplayValue($value);
    }

    private function normalizeDisplayValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    private function normalizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }
}
