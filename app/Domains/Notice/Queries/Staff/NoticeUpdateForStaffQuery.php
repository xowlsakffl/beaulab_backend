<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Domains\Notice\Models\Notice;

/**
 * NoticeUpdateForStaffQuery 역할 정의.
 * 공지사항 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
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
            'updated_by_staff_id' => $payload['updated_by_staff_id'] ?? $notice->updated_by_staff_id,
        ]);

        if ($notice->isDirty()) {
            $notice->save();
        }

        return $notice->fresh();
    }
}
