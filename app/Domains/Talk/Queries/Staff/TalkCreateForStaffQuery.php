<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;

/**
 * TalkCreateForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkCreateForStaffQuery
{
    public function create(array $payload): Talk
    {
        return Talk::create([
            'author_id' => $payload['author_id'] ?? null,
            'title' => $payload['title'],
            'content' => $payload['content'],
            'status' => $payload['status'] ?? Talk::STATUS_ACTIVE,
            'is_visible' => (bool) ($payload['is_visible'] ?? true),
            'author_ip' => $payload['author_ip'] ?? null,
            'is_pinned' => (bool) ($payload['is_pinned'] ?? false),
            'pinned_order' => (int) ($payload['pinned_order'] ?? 0),
        ]);
    }
}
