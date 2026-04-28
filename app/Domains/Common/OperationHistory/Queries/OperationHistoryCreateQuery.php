<?php

namespace App\Domains\Common\OperationHistory\Queries;

use App\Domains\Common\OperationHistory\Models\OperationHistory;

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
