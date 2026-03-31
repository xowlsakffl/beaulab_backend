<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;

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
