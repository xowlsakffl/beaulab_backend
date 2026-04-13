<?php

namespace App\Domains\Common\Models\Concerns;

use App\Domains\Common\Models\AdminNote\AdminNote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * HasAdminNotes 역할 정의.
 * 공통 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
trait HasAdminNotes
{
    public function adminNotes(): MorphMany
    {
        return $this->morphMany(AdminNote::class, 'target', 'target_type', 'target_id')
            ->latest('id');
    }
}
