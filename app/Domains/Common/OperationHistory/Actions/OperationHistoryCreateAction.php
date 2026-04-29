<?php

namespace App\Domains\Common\OperationHistory\Actions;

use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Queries\OperationHistoryCreateQuery;
use App\Domains\Common\OperationHistory\Support\OperationHistoryActorRegistry;
use App\Domains\Common\OperationHistory\Support\OperationHistoryTargetRegistry;
use Illuminate\Database\Eloquent\Model;

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
        ?string $field = null,
        mixed $beforeValue = null,
        mixed $afterValue = null,
        ?string $reason = null,
        array $metadata = [],
        ?string $actorKind = null,
    ): OperationHistory {
        OperationHistoryTargetRegistry::assertSupported($target);

        return $this->query->create([
            'target_type' => $target::class,
            'target_id' => (int) $target->getKey(),
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor ? (int) $actor->getKey() : null,
            'actor_kind' => $actorKind ?? OperationHistoryActorRegistry::kindForActor($actor),
            'action' => $action,
            'field' => $field,
            'before_value' => $this->normalizeValue($beforeValue),
            'after_value' => $this->normalizeValue($afterValue),
            'reason' => $this->normalizeReason($reason),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function normalizeValue(mixed $value): ?string
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
