<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Domains\Notice\Models\Notice;

final class NoticeUpdateForStaffQuery
{
    public function update(Notice $notice, array $payload): Notice
    {
        $notice->fill([
            'channel' => array_key_exists('channel', $payload) ? (string) $payload['channel'] : $notice->channel,
            'title' => array_key_exists('title', $payload) ? (string) $payload['title'] : $notice->title,
            'content' => array_key_exists('content', $payload) ? (string) $payload['content'] : $notice->content,
            'status' => array_key_exists('status', $payload) ? (string) $payload['status'] : $notice->status,
            'is_pinned' => array_key_exists('is_pinned', $payload) ? (bool) $payload['is_pinned'] : $notice->is_pinned,
            'is_publish_period_unlimited' => array_key_exists('is_publish_period_unlimited', $payload)
                ? (bool) $payload['is_publish_period_unlimited']
                : $notice->is_publish_period_unlimited,
            'publish_start_at' => array_key_exists('publish_start_at', $payload)
                ? $payload['publish_start_at']
                : $notice->publish_start_at,
            'publish_end_at' => array_key_exists('publish_end_at', $payload)
                ? $payload['publish_end_at']
                : $notice->publish_end_at,
            'is_important' => array_key_exists('is_important', $payload)
                ? (bool) $payload['is_important']
                : $notice->is_important,
            'updated_by_staff_id' => $payload['updated_by_staff_id'] ?? $notice->updated_by_staff_id,
        ]);

        if ($notice->isDirty()) {
            $notice->save();
        }

        return $notice->fresh();
    }
}
