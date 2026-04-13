<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\TalkComment;

/**
 * TalkCommentCreateForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkCommentCreateForStaffQuery
{
    public function create(array $payload): TalkComment
    {
        return TalkComment::create([
            'talk_id' => (int) $payload['talk_id'],
            'parent_id' => $payload['parent_id'] ?? null,
            'author_id' => $payload['author_id'] ?? null,
            'content' => $payload['content'],
            'status' => $payload['status'] ?? TalkComment::STATUS_ACTIVE,
            'is_visible' => (bool) ($payload['is_visible'] ?? true),
            'author_ip' => $payload['author_ip'] ?? null,
        ]);
    }
}
