<?php

namespace App\Domains\Talk\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;

/**
 * TalkCommentMentionSyncForStaffQuery 역할 정의.
 * 토크 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class TalkCommentMentionSyncForStaffQuery
{
    /**
     * @param array{user_id:int, mention_text?:string|null}|null $mention
     */
    public function sync(TalkComment $comment, ?array $mention, ?int $mentionedByUserId): void
    {
        $normalized = $this->normalizeMention($mention);
        if ($normalized === null) {
            $comment->mentions()->delete();
            return;
        }

        $userName = AccountUser::query()
            ->where('id', $normalized['user_id'])
            ->value('name');

        $mentionText = $userName ? '@' . (string) $userName : null;
        $startOffset = $mentionText !== null ? 0 : null;
        $endOffset = $mentionText !== null ? mb_strlen($mentionText) : null;

        TalkCommentMention::query()->updateOrCreate(
            [
                'talk_comment_id' => (int) $comment->id,
            ],
            [
                'mentioned_user_id' => (int) $normalized['user_id'],
                'mentioned_by_user_id' => $mentionedByUserId,
                'mention_text' => $mentionText,
                'start_offset' => $startOffset,
                'end_offset' => $endOffset,
            ],
        );
    }

    /**
     * @param array{user_id:int, mention_text?:string|null}|null $mention
     * @return array{user_id:int}|null
     */
    private function normalizeMention(?array $mention): ?array
    {
        if (! $mention || ! isset($mention['user_id'])) {
            return null;
        }

        $normalized = ['user_id' => (int) $mention['user_id']];

        return $normalized['user_id'] > 0 ? $normalized : null;
    }
}
