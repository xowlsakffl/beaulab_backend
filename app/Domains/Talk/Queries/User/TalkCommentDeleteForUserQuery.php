<?php

namespace App\Domains\Talk\Queries\User;

use App\Domains\Talk\Models\TalkComment;

final class TalkCommentDeleteForUserQuery
{
    public function getOwnedForUpdate(int $talkId, int $commentId, int $authorId): ?TalkComment
    {
        return TalkComment::query()
            ->whereKey($commentId)
            ->where('talk_id', $talkId)
            ->where('author_id', $authorId)
            ->lockForUpdate()
            ->first();
    }

    public function markDeleted(TalkComment $comment, string $status, string $postStatus): TalkComment
    {
        $comment->forceFill([
            'status' => $status,
            'post_status' => $postStatus,
        ]);

        if ($comment->isDirty()) {
            $comment->save();
        }

        return $comment->fresh();
    }
}
