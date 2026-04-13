<?php

namespace App\Domains\Chat\Queries;

use App\Domains\Chat\Models\ChatMessage;

final class ChatMessageBroadcastPayloadQuery
{
    public function payload(int $messageId): ?array
    {
        $message = ChatMessage::query()
            ->with('sender:id,name,email')
            ->find($messageId);

        if (! $message instanceof ChatMessage) {
            return null;
        }

        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->chat_id,
            'sender_user_id' => (int) $message->sender_user_id,
            'message_type' => (string) $message->message_type,
            'body' => $message->body,
            'reply_to_message_id' => $message->reply_to_message_id ? (int) $message->reply_to_message_id : null,
            'metadata' => $message->metadata,
            'sender' => $message->relationLoaded('sender') && $message->sender
                ? [
                    'id' => (int) $message->sender->id,
                    'name' => (string) $message->sender->name,
                    'email' => (string) $message->sender->email,
                ]
                : null,
            'edited_at' => $message->edited_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
            'updated_at' => $message->updated_at?->toISOString(),
        ];
    }
}
