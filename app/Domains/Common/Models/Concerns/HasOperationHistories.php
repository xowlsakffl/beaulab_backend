<?php

namespace App\Domains\Common\Models\Concerns;

use App\Domains\Common\Models\OperationHistory\OperationHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasOperationHistories 역할 정의.
 * 운영/시스템 액션 히스토리를 공통 폴리모픽 관계로 제공한다.
 */
trait HasOperationHistories
{
    public function operationHistories(): MorphMany
    {
        return $this->morphMany(OperationHistory::class, 'target', 'target_type', 'target_id')
            ->latest('id');
    }
}
