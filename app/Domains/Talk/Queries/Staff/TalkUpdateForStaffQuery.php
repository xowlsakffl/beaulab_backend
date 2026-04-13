<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\Talk;

/**
 * TalkUpdateForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkUpdateForStaffQuery
{
    public function update(Talk $talk, array $payload): Talk
    {
        $talk->fill([
            'author_id' => array_key_exists('author_id', $payload) ? $payload['author_id'] : $talk->author_id,
            'title' => array_key_exists('title', $payload) ? $payload['title'] : $talk->title,
            'content' => array_key_exists('content', $payload) ? $payload['content'] : $talk->content,
            'status' => array_key_exists('status', $payload) ? $payload['status'] : $talk->status,
            'is_visible' => array_key_exists('is_visible', $payload) ? (bool) $payload['is_visible'] : $talk->is_visible,
            'author_ip' => array_key_exists('author_ip', $payload) ? $payload['author_ip'] : $talk->author_ip,
            'is_pinned' => array_key_exists('is_pinned', $payload) ? (bool) $payload['is_pinned'] : $talk->is_pinned,
            'pinned_order' => array_key_exists('pinned_order', $payload) ? (int) $payload['pinned_order'] : $talk->pinned_order,
        ]);

        if ($talk->isDirty()) {
            $talk->save();
        }

        return $talk->fresh();
    }
}
