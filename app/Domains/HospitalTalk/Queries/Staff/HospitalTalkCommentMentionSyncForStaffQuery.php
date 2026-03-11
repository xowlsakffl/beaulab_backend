<?php

namespace App\Domains\HospitalTalk\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Domains\HospitalTalk\Models\HospitalTalkCommentMention;
use Illuminate\Support\Collection;

final class HospitalTalkCommentMentionSyncForStaffQuery
{
    /**
     * @param array<int, array{
     *     user_id:int,
     *     mention_text?:string|null
     * }> $mentions
     */
    public function sync(HospitalTalkComment $comment, array $mentions, ?int $mentionedByUserId): void
    {
        $normalized = $this->normalizeMentions($mentions);
        $userIds = array_column($normalized, 'user_id');

        if ($userIds === []) {
            $comment->mentions()->delete();
            return;
        }

        $userNameMap = AccountUser::query()
            ->whereIn('id', $userIds)
            ->pluck('name', 'id')
            ->all();

        $mention = $normalized[0];
        $fallbackText = isset($userNameMap[$mention['user_id']]) ? '@' . (string) $userNameMap[$mention['user_id']] : null;
        $mentionText = $mention['mention_text'] ?: $fallbackText;
        $startOffset = $mentionText !== null ? 0 : null;
        $endOffset = $mentionText !== null ? mb_strlen($mentionText) : null;

        HospitalTalkCommentMention::query()->updateOrCreate(
            [
                'hospital_talk_comment_id' => (int) $comment->id,
            ],
            [
                'mentioned_user_id' => (int) $mention['user_id'],
                'mentioned_by_user_id' => $mentionedByUserId,
                'mention_text' => $mentionText,
                'start_offset' => $startOffset,
                'end_offset' => $endOffset,
            ],
        );
    }

    /**
     * @param array<int, array{
     *     user_id:int,
     *     mention_text?:string|null
     * }> $mentions
     * @return array<int, array{
     *     user_id:int,
     *     mention_text?:string|null
     * }>
     */
    private function normalizeMentions(array $mentions): array
    {
        return collect($mentions)
            ->filter(static fn ($mention): bool => is_array($mention) && isset($mention['user_id']))
            ->map(static fn (array $mention): array => [
                    'user_id' => (int) $mention['user_id'],
                    'mention_text' => isset($mention['mention_text']) ? trim((string) $mention['mention_text']) : null,
                ])
            ->filter(static fn (array $mention): bool => $mention['user_id'] > 0)
            ->groupBy('user_id')
            ->map(function (Collection $group): array {
                $first = $group->first();
                return is_array($first) ? $first : [];
            })
            ->filter(static fn (array $mention): bool => $mention !== [])
            ->take(1)
            ->values()
            ->all();
    }
}
