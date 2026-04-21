<?php

namespace App\Domains\Common\Queries\AdminActionHistory;

use App\Domains\Common\Models\AdminActionHistory\AdminActionHistory;

/**
 * AdminActionHistoryCreateQuery 역할 정의.
 * 운영자 액션 히스토리 생성 쿼리를 캡슐화한다.
 */
final class AdminActionHistoryCreateQuery
{
    public function create(array $payload): AdminActionHistory
    {
        return AdminActionHistory::create($payload);
    }
}
