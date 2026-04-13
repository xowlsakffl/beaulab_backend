<?php

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Facades\Broadcast;

// 앱 사용자 개인 알림 채널. 본인 userId 채널만 구독할 수 있다.
Broadcast::channel('user.{userId}', function (AccountUser $user, int $userId): bool {
    return (int) $user->id === $userId;
});

// 채팅방 실시간 채널. 해당 채팅방 participant만 구독할 수 있다.
Broadcast::channel('chat.{chatId}', function (AccountUser $user, int $chatId): bool {
    return ChatParticipant::query()
        ->where('chat_id', $chatId)
        ->where('account_user_id', $user->id)
        ->exists();
});
