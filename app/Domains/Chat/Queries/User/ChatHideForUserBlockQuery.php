<?php

namespace App\Domains\Chat\Queries\User;

use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Support\ChatMatchKey;

/**
 * 사용자 차단 시 내 채팅방 숨김 처리 쿼리.
 * 차단한 사용자 기준으로 기존 채팅방을 숨김 상태로 갱신한다.
 */
final class ChatHideForUserBlockQuery
{
    public function hideForBlocker(int $blockerUserId, int $blockedUserId): void
    {
        $chat = Chat::withTrashed()
            ->where('match_key', ChatMatchKey::forUsers($blockerUserId, $blockedUserId))
            ->lockForUpdate()
            ->first();

        if (! $chat instanceof Chat) {
            return;
        }

        $participant = $chat->participants()
            ->where('account_user_id', $blockerUserId)
            ->lockForUpdate()
            ->first();

        if ($participant === null) {
            return;
        }

        $lastMessageId = $chat->last_message_id ? (int) $chat->last_message_id : null;

        $participant->forceFill([
            'deleted_until_message_id' => $lastMessageId,
            'deleted_at' => now(),
            'last_read_message_id' => $lastMessageId ?? $participant->last_read_message_id,
            'last_read_at' => $lastMessageId !== null ? now() : $participant->last_read_at,
        ])->save();
    }
}
