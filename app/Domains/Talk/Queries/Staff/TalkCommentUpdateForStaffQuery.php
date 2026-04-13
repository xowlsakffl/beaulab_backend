<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\Talk\Models\TalkComment;

/**
 * TalkCommentUpdateForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkCommentUpdateForStaffQuery
{
    public function update(TalkComment $comment, array $payload): TalkComment
    {
        $comment->fill([
            'parent_id' => array_key_exists('parent_id', $payload) ? $payload['parent_id'] : $comment->parent_id,
            'author_id' => array_key_exists('author_id', $payload) ? $payload['author_id'] : $comment->author_id,
            'content' => array_key_exists('content', $payload) ? $payload['content'] : $comment->content,
            'status' => array_key_exists('status', $payload) ? $payload['status'] : $comment->status,
            'is_visible' => array_key_exists('is_visible', $payload) ? (bool) $payload['is_visible'] : $comment->is_visible,
        ]);

        if ($comment->isDirty()) {
            $comment->save();
        }

        return $comment->fresh();
    }
}
