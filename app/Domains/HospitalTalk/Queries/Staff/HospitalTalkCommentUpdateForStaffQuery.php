<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\HospitalTalk\Models\HospitalTalkComment;

final class HospitalTalkCommentUpdateForStaffQuery
{
    public function update(HospitalTalkComment $comment, array $payload): HospitalTalkComment
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
