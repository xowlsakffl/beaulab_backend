<?php

namespace App\Domains\Faq\Queries\Staff;

use App\Domains\Faq\Models\Faq;

/**
 * FaqCreateForStaffQuery 역할 정의.
 * FAQ 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class FaqCreateForStaffQuery
{
    public function create(array $payload): Faq
    {
        return Faq::create([
            'channel' => (string) $payload['channel'],
            'question' => (string) $payload['question'],
            'content' => (string) $payload['content'],
            'status' => (string) ($payload['status'] ?? Faq::STATUS_ACTIVE),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'view_count' => (int) ($payload['view_count'] ?? 0),
            'created_by_staff_id' => $payload['created_by_staff_id'] ?? null,
            'updated_by_staff_id' => $payload['updated_by_staff_id'] ?? null,
        ]);
    }
}
