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
            'is_visible' => array_key_exists('is_visible', $payload) ? (bool) $payload['is_visible'] : $notice->is_visible,
            'is_pinned' => array_key_exists('is_pinned', $payload) ? (bool) $payload['is_pinned'] : $notice->is_pinned,
            'pinned_order' => array_key_exists('pinned_order', $payload) ? (int) $payload['pinned_order'] : $notice->pinned_order,
            'is_publish_period_unlimited' => array_key_exists('is_publish_period_unlimited', $payload)
                ? (bool) $payload['is_publish_period_unlimited']
                : $notice->is_publish_period_unlimited,
            'publish_start_at' => array_key_exists('publish_start_at', $payload)
                ? $payload['publish_start_at']
                : $notice->publish_start_at,
            'publish_end_at' => array_key_exists('publish_end_at', $payload)
                ? $payload['publish_end_at']
                : $notice->publish_end_at,
            'is_push_enabled' => array_key_exists('is_push_enabled', $payload)
                ? (bool) $payload['is_push_enabled']
                : $notice->is_push_enabled,
            'push_sent_at' => array_key_exists('push_sent_at', $payload)
                ? $payload['push_sent_at']
                : $notice->push_sent_at,
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
