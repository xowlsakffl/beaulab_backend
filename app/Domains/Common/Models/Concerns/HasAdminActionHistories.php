<?php

namespace App\Domains\Common\Models\Concerns;

use App\Domains\Common\Models\AdminActionHistory\AdminActionHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasAdminActionHistories 역할 정의.
 * 운영자 액션 히스토리를 공통 폴리모픽 관계로 제공한다.
 */
trait HasAdminActionHistories
{
    public function adminActionHistories(): MorphMany
    {
        return $this->morphMany(AdminActionHistory::class, 'target', 'target_type', 'target_id')
            ->latest('id');
    }
}
