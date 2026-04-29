<?php

namespace App\Domains\Talk\Queries\User;

use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;

final class TalkCommentCreateForUserQuery
{
    public function getTalkForUpdate(int $talkId): ?Talk
    {
        return Talk::query()
            ->whereKey($talkId)
            ->lockForUpdate()
            ->first();
    }

    public function getParentForUpdate(int $talkId, int $parentId): ?TalkComment
    {
        return TalkComment::query()
            ->whereKey($parentId)
            ->where('talk_id', $talkId)
            ->lockForUpdate()
            ->first(['id', 'talk_id', 'parent_id', 'status', 'post_status']);
    }

    public function create(array $payload): TalkComment
    {
        return TalkComment::query()->create([
            'talk_id' => (int) $payload['talk_id'],
            'parent_id' => $payload['parent_id'] ?? null,
            'author_id' => (int) $payload['author_id'],
            'content' => (string) $payload['content'],
            'status' => TalkComment::STATUS_ACTIVE,
            'post_status' => TalkComment::POST_STATUS_NORMAL,
            'author_ip' => $payload['author_ip'] ?? null,
            'like_count' => 0,
        ]);
    }

    public function incrementTalkCommentCount(Talk $talk): void
    {
        Talk::query()
            ->whereKey((int) $talk->id)
            ->increment('comment_count');
    }
}
