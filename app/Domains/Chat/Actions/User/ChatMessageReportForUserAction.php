<?php

namespace App\Domains\Chat\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use App\Domains\Chat\Queries\User\ChatMessageReportForUserQuery;
use App\Domains\Common\ContentReport\Actions\User\ContentReportCreateForUserAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ChatMessageReportForUserAction
{
    public function __construct(
        private readonly ChatMessageReportForUserQuery $query,
        private readonly ContentReportCreateForUserAction $reportCreateAction,
    ) {}

    public function execute(Chat $chat, AccountUser $user, array $payload): array
    {
        $participant = $this->participant($chat, (int) $user->id);
        $messageIds = $this->messageIds($payload['message_ids'] ?? []);
        $messages = $this->messages($chat, $participant, $messageIds);

        if ($messages->contains(static fn (ChatMessage $message): bool => (int) $message->sender_user_id === (int) $user->id)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인이 작성한 메시지는 신고할 수 없습니다.');
        }

        $representativeMessage = $messages->first();
        if (! $representativeMessage instanceof ChatMessage) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신고할 메시지를 확인해 주세요.');
        }

        $result = $this->reportCreateAction->execute($user, $representativeMessage, [
            'reason' => $payload['reason'],
            'reason_text' => $payload['reason_text'] ?? null,
            'reporter_ip' => $payload['reporter_ip'] ?? null,
            'content_snapshot' => $this->contentSnapshot($messages),
            'metadata' => [
                'kind' => 'chat_message_group',
                'chat_id' => (int) $chat->id,
                'reported_message_ids' => $messageIds,
                'reported_messages' => $messages
                    ->map(fn (ChatMessage $message): array => $this->messageMetadata($message))
                    ->values()
                    ->all(),
            ],
        ]);

        return [
            ...$result,
            'chat_id' => (int) $chat->id,
            'representative_message_id' => (int) $representativeMessage->id,
            'reported_message_ids' => $messageIds,
        ];
    }

    private function participant(Chat $chat, int $userId): ChatParticipant
    {
        $participant = $this->query->participant($chat, $userId);

        if (! $participant instanceof ChatParticipant) {
            throw new CustomException(ErrorCode::FORBIDDEN, '채팅방 참여자만 메시지를 신고할 수 있습니다.');
        }

        return $participant;
    }

    /**
     * @return array<int, int>
     */
    private function messageIds(mixed $value): array
    {
        if (! is_array($value)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신고할 메시지를 확인해 주세요.');
        }

        return array_values(array_unique(array_map(static fn (mixed $messageId): int => (int) $messageId, $value)));
    }

    /**
     * @param  array<int, int>  $messageIds
     * @return Collection<int, ChatMessage>
     */
    private function messages(Chat $chat, ChatParticipant $participant, array $messageIds): Collection
    {
        $messages = $this->query->messages(
            $chat,
            $messageIds,
            (int) ($participant->deleted_until_message_id ?? 0),
        )->keyBy('id');

        if ($messages->count() !== count($messageIds)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 채팅방의 메시지만 신고할 수 있습니다.');
        }

        return collect($messageIds)
            ->map(static fn (int $messageId): ?ChatMessage => $messages->get($messageId))
            ->filter(static fn (?ChatMessage $message): bool => $message instanceof ChatMessage)
            ->values();
    }

    /**
     * @param  Collection<int, ChatMessage>  $messages
     */
    private function contentSnapshot(Collection $messages): string
    {
        return Str::limit($messages
            ->map(fn (ChatMessage $message): string => sprintf(
                '#%d %s %s: %s',
                (int) $message->id,
                $message->created_at?->toISOString() ?? '-',
                $this->senderLabel($message),
                $this->messageBody($message),
            ))
            ->implode("\n"), 2000, '');
    }

    private function messageMetadata(ChatMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'sender_user_id' => (int) $message->sender_user_id,
            'sender_nickname' => $this->senderLabel($message),
            'message_type' => (string) $message->message_type,
            'body' => $message->body,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    private function senderLabel(ChatMessage $message): string
    {
        if ($message->relationLoaded('sender') && $message->sender) {
            return (string) ($message->sender->nickname ?: $message->sender->name ?: "user:{$message->sender_user_id}");
        }

        return "user:{$message->sender_user_id}";
    }

    private function messageBody(ChatMessage $message): string
    {
        $body = trim((string) $message->body);

        if ($body !== '') {
            return $body;
        }

        return '['.(string) $message->message_type.']';
    }
}
