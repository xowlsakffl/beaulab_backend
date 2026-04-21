<?php

namespace App\Domains\Common\Queries\OperationHistory;

use App\Domains\Common\Models\OperationHistory\OperationHistory;

/**
 * OperationHistoryCreateQuery 역할 정의.
 * 운영 히스토리 생성 쿼리를 캡슐화한다.
 */
final class OperationHistoryCreateQuery
{
    public function create(array $payload): OperationHistory
    {
        return OperationHistory::create($payload);
    }
}
