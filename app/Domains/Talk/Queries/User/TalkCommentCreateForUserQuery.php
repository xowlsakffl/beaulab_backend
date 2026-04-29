<?php

namespace App\Domains\Talk\Queries\User;

use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;

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

    public function createMention(TalkComment $comment, array $payload): TalkCommentMention
    {
        return $comment->mentions()->create([
            'mentioned_user_id' => (int) $payload['mentioned_user_id'],
            'mentioned_by_user_id' => isset($payload['mentioned_by_user_id'])
                ? (int) $payload['mentioned_by_user_id']
                : null,
            'mention_text' => $payload['mention_text'] ?? null,
            'start_offset' => isset($payload['start_offset']) ? (int) $payload['start_offset'] : null,
            'end_offset' => isset($payload['end_offset']) ? (int) $payload['end_offset'] : null,
        ]);
    }

    public function incrementTalkCommentCount(Talk $talk): void
    {
        Talk::query()
            ->whereKey((int) $talk->id)
            ->increment('comment_count');
    }
}
