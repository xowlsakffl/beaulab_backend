<?php

namespace App\Domains\Faq\Queries\Staff;

use App\Domains\Faq\Models\Faq;

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
