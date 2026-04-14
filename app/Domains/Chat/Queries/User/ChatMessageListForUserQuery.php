<?php

namespace App\Domains\Chat\Queries\User;

use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Collection;

/**
 * 채팅 메시지 cursor 조회 쿼리.
 * after_id는 정방향, before_id 또는 기본 조회는 역방향으로 가져온다.
 */
final class ChatMessageListForUserQuery
{
    /**
     * @return array{items: Collection<int, ChatMessage>, meta: array<string, mixed>}
     */
    public function get(Chat $chat, ChatParticipant $participant, array $filters): array
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 30), 100));
        $afterId = (int) ($filters['after_id'] ?? 0);
        $beforeId = (int) ($filters['before_id'] ?? 0);
        $deletedUntilMessageId = (int) ($participant->deleted_until_message_id ?? 0);

        $builder = ChatMessage::query()
            ->where('chat_id', $chat->id)
            ->with(['sender:id,name,email', 'attachments']);

        if ($deletedUntilMessageId > 0) {
            $builder->where('id', '>', $deletedUntilMessageId);
        }

        if ($afterId > 0) {
            $builder->where('id', '>', $afterId)->orderBy('id');
        } else {
            if ($beforeId > 0) {
                $builder->where('id', '<', $beforeId);
            }

            $builder->orderByDesc('id');
        }

        $items = $builder->limit($perPage + 1)->get();
        $hasMore = $items->count() > $perPage;

        if ($hasMore) {
            $items = $items->take($perPage);
        }

        return [
            'items' => $items->values(),
            'meta' => [
                'per_page' => $perPage,
                'has_more' => $hasMore,
                'after_id' => $afterId > 0 ? $afterId : null,
                'before_id' => $beforeId > 0 ? $beforeId : null,
                'order' => $afterId > 0 ? 'asc' : 'desc',
            ],
        ];
    }

    public function participant(Chat $chat, int $userId): ?ChatParticipant
    {
        return $chat->participants()
            ->where('account_user_id', $userId)
            ->first();
    }
}
