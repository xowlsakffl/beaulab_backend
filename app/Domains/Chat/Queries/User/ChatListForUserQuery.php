<?php

namespace App\Domains\Chat\Queries\User;

use App\Domains\Chat\Models\Chat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 내 채팅방 목록 조회 쿼리.
 * 마지막 메시지와 상대 사용자 정보를 함께 로드하고, 현재 사용자 기준 미읽음 수를 계산한다.
 */
final class ChatListForUserQuery
{
    public function paginate(int $userId, array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 50));
        $includeClosed = (bool) ($filters['include_closed'] ?? false);

        $builder = Chat::query()
            ->whereHas('participants', fn ($query) => $query->where('account_user_id', $userId))
            ->with([
                'lastMessage.sender:id,name,email',
                'lastMessage.attachments',
                'participants.accountUser:id,name,email',
            ])
            ->withCount([
                'messages as unread_count' => function ($query) use ($userId): void {
                    $query
                        ->where('sender_user_id', '!=', $userId)
                        ->whereRaw(
                            'chat_messages.id > COALESCE((select cp.last_read_message_id from chat_participants cp where cp.chat_id = chats.id and cp.account_user_id = ? limit 1), 0)',
                            [$userId]
                        );
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if (! $includeClosed) {
            $builder->where('status', Chat::STATUS_ACTIVE);
        }

        return $builder->paginate($perPage)->withQueryString();
    }
}
