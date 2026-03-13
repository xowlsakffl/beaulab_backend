<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Domains\Notice\Models\Notice;

final class NoticeCreateForStaffQuery
{
    public function create(array $payload): Notice
    {
        return Notice::create([
            'channel' => (string) $payload['channel'],
            'title' => (string) $payload['title'],
            'content' => (string) $payload['content'],
            'status' => (string) ($payload['status'] ?? Notice::STATUS_ACTIVE),
            'is_pinned' => (bool) ($payload['is_pinned'] ?? false),
            'pinned_order' => (int) ($payload['pinned_order'] ?? 0),
            'is_publish_period_unlimited' => (bool) ($payload['is_publish_period_unlimited'] ?? true),
            'publish_start_at' => $payload['publish_start_at'] ?? null,
            'publish_end_at' => $payload['publish_end_at'] ?? null,
            'is_important' => (bool) ($payload['is_important'] ?? false),
            'view_count' => (int) ($payload['view_count'] ?? 0),
            'created_by_staff_id' => $payload['created_by_staff_id'] ?? null,
            'updated_by_staff_id' => $payload['updated_by_staff_id'] ?? null,
        ]);
    }
}
