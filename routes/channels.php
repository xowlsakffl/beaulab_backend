<?php

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\ChatParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function (AccountUser $user, int $userId): bool {
    return (int) $user->id === $userId;
});

Broadcast::channel('chat.{chatId}', function (AccountUser $user, int $chatId): bool {
    return ChatParticipant::query()
        ->where('chat_id', $chatId)
        ->where('account_user_id', $user->id)
        ->exists();
});
