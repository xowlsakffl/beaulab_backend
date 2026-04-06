<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\TalkComment;

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
