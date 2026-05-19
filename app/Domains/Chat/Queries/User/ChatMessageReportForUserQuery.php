<?php

namespace App\Domains\Chat\Queries\User;

use App\Domains\AccountUser\Models\AccountUserBlock;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Collection;

final class ChatMessageReportForUserQuery
{
    public function participant(Chat $chat, int $userId): ?ChatParticipant
    {
        return $chat->participants()
            ->where('account_user_id', $userId)
            ->first();
    }

    /**
     * @param  array<int, int>  $messageIds
     * @return Collection<int, ChatMessage>
     */
    public function messages(Chat $chat, array $messageIds, int $deletedUntilMessageId): Collection
    {
        return ChatMessage::query()
            ->where('chat_id', $chat->id)
            ->whereIn('id', $messageIds)
            ->when($deletedUntilMessageId > 0, fn ($query) => $query->where('id', '>', $deletedUntilMessageId))
            ->with('sender:id,name,nickname,email')
            ->get();
    }

    public function isBlockedBy(int $blockerUserId, int $blockedUserId): bool
    {
        return AccountUserBlock::query()
            ->where('blocker_user_id', $blockerUserId)
            ->where('blocked_user_id', $blockedUserId)
            ->exists();
    }

    public function hasBlockedPeer(Chat $chat, int $userId): bool
    {
        $peerUserId = $chat->participants()
            ->where('account_user_id', '!=', $userId)
            ->value('account_user_id');

        if ($peerUserId === null) {
            return false;
        }

        return $this->isBlockedBy($userId, (int) $peerUserId);
    }
}
