<?php

namespace App\Domains\Common\Actions\AdminActionHistory;

use App\Domains\Common\Models\AdminActionHistory\AdminActionHistory;
use App\Domains\Common\Queries\AdminActionHistory\AdminActionHistoryCreateQuery;
use Illuminate\Database\Eloquent\Model;

/**
 * AdminActionHistoryCreateAction 역할 정의.
 * 상세 화면에 표시할 운영자 액션 히스토리를 생성한다.
 */
final class AdminActionHistoryCreateAction
{
    public function __construct(
        private readonly AdminActionHistoryCreateQuery $query,
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
    ): AdminActionHistory {
        return $this->query->create([
            'target_type' => $target::class,
            'target_id' => (int) $target->getKey(),
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor ? (int) $actor->getKey() : null,
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
