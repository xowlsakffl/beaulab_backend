<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalkComment;

final class HospitalTalkCommentCreateForStaffQuery
{
    public function create(array $payload): HospitalTalkComment
    {
        return HospitalTalkComment::create([
            'hospital_talk_id' => (int) $payload['hospital_talk_id'],
            'parent_id' => $payload['parent_id'] ?? null,
            'author_id' => $payload['author_id'] ?? null,
            'content' => $payload['content'],
            'status' => $payload['status'] ?? HospitalTalkComment::STATUS_ACTIVE,
            'is_visible' => (bool) ($payload['is_visible'] ?? true),
            'author_ip' => $payload['author_ip'] ?? null,
        ]);
    }
}
