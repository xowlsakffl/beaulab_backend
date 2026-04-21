<?php

namespace App\Domains\Common\Actions\OperationHistory;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\OperationHistory\OperationHistory;
use App\Domains\Common\Queries\OperationHistory\OperationHistoryCreateQuery;
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
        return $this->query->create([
            'target_type' => $target::class,
            'target_id' => (int) $target->getKey(),
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor ? (int) $actor->getKey() : null,
            'actor_kind' => $actorKind ?? $this->inferActorKind($actor),
            'action' => $action,
            'field' => $field,
            'before_value' => $this->normalizeValue($beforeValue),
            'after_value' => $this->normalizeValue($afterValue),
            'reason' => $this->normalizeReason($reason),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    private function inferActorKind(?Model $actor): string
    {
        return match (true) {
            $actor instanceof AccountStaff => OperationHistory::ACTOR_KIND_STAFF,
            $actor instanceof AccountHospital => OperationHistory::ACTOR_KIND_HOSPITAL,
            $actor instanceof AccountBeauty => OperationHistory::ACTOR_KIND_BEAUTY,
            $actor instanceof AccountUser => OperationHistory::ACTOR_KIND_USER,
            $actor === null => OperationHistory::ACTOR_KIND_SYSTEM,
            default => OperationHistory::ACTOR_KIND_UNKNOWN,
        };
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
