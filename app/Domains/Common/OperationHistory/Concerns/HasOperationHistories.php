<?php

namespace App\Domains\Common\OperationHistory\Concerns;

use App\Domains\Common\OperationHistory\Models\OperationHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasOperationHistories 역할 정의.
 * 운영/시스템 액션 히스토리를 공통 폴리모픽 관계로 제공한다.
 */
trait HasOperationHistories
{
    public function loadLatestStatusHistory(): static
    {
        $histories = $this->operationHistories()
            ->whereHas('changes', fn ($query) => $query
                ->where('field_key', 'status')
                ->where('after_value', json_encode((string) $this->status, JSON_THROW_ON_ERROR)))
            ->with('actor')
            ->limit(1)
            ->get();

        return $this->setRelation('operationHistories', $histories);
    }

    public function operationHistories(): MorphMany
    {
        return $this->morphMany(OperationHistory::class, 'target', 'target_type', 'target_id')
            ->latest('id');
    }
}
