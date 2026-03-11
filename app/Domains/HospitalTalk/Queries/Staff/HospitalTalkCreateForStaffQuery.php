<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalk;

final class HospitalTalkCreateForStaffQuery
{
    public function create(array $payload): HospitalTalk
    {
        return HospitalTalk::create([
            'author_id' => $payload['author_id'] ?? null,
            'title' => $payload['title'],
            'content' => $payload['content'],
            'status' => $payload['status'] ?? HospitalTalk::STATUS_ACTIVE,
            'is_visible' => (bool) ($payload['is_visible'] ?? true),
            'author_ip' => $payload['author_ip'] ?? null,
            'is_pinned' => (bool) ($payload['is_pinned'] ?? false),
            'pinned_order' => (int) ($payload['pinned_order'] ?? 0),
        ]);
    }
}
