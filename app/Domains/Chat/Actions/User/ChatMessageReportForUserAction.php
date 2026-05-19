<?php

namespace App\Domains\Chat\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\AccountUserBlockCreateForUserQuery;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Models\ChatParticipant;
use App\Domains\Chat\Queries\User\ChatHideForUserBlockQuery;
use App\Domains\Chat\Queries\User\ChatMessageReportForUserQuery;
use App\Domains\Common\ContentReport\Actions\User\ContentReportCreateForUserAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ChatMessageReportForUserAction
{
    public function __construct(
        private readonly ChatMessageReportForUserQuery $query,
        private readonly ContentReportCreateForUserAction $reportCreateAction,
        private readonly AccountUserBlockCreateForUserQuery $blockCreateQuery,
        private readonly ChatHideForUserBlockQuery $chatHideQuery,
    ) {}

    public function execute(Chat $chat, AccountUser $user, array $payload): void
    {
        $participant = $this->participant($chat, (int) $user->id);
        $messageIds = $this->messageIds($payload['message_ids'] ?? []);

        if ($this->query->hasBlockedPeer($chat, (int) $user->id)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 차단한 사용자의 메시지는 신고할 수 없습니다.');
        }

        $messages = $this->messages($chat, $participant, $messageIds);

        if ($messages->contains(static fn (ChatMessage $message): bool => (int) $message->sender_user_id === (int) $user->id)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인이 작성한 메시지는 신고할 수 없습니다.');
        }

        $reportedUserId = $this->reportedUserId($messages);
        if ($this->query->isBlockedBy((int) $user->id, $reportedUserId)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '이미 차단한 사용자의 메시지는 신고할 수 없습니다.');
        }

        $representativeMessage = $messages->first();
        if (! $representativeMessage instanceof ChatMessage) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신고할 메시지를 확인해 주세요.');
        }

        DB::transaction(function () use ($user, $representativeMessage, $payload, $messages, $reportedUserId): void {
            $this->reportCreateAction->execute($user, $representativeMessage, [
                'reason' => $payload['reason'],
                'reason_text' => $payload['reason_text'] ?? null,
                'reporter_ip' => $payload['reporter_ip'] ?? null,
                'content_snapshot' => $this->contentSnapshot($messages),
                'items' => $this->reportItems($messages),
            ]);

            $block = $this->blockCreateQuery->create($user, $reportedUserId);
            $this->chatHideQuery->hideForBlocker((int) $user->id, (int) $block->blocked_user_id);
        });
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
    private function reportedUserId(Collection $messages): int
    {
        $senderIds = $messages
            ->pluck('sender_user_id')
            ->map(static fn (mixed $senderUserId): int => (int) $senderUserId)
            ->unique()
            ->values();

        if ($senderIds->count() !== 1) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '같은 사용자의 메시지만 한 번에 신고할 수 있습니다.');
        }

        return (int) $senderIds->first();
    }

    /**
     * @param  Collection<int, ChatMessage>  $messages
     */
    private function contentSnapshot(Collection $messages): string
    {
        return Str::limit($messages
            ->map(fn (ChatMessage $message): string => $this->messageSnapshot($message))
            ->implode("\n"), 2000, '');
    }

    /**
     * @param  Collection<int, ChatMessage>  $messages
     * @return array<int, array{target_type: class-string<ChatMessage>, target_id: int, target_author_id: int, content_snapshot: string}>
     */
    private function reportItems(Collection $messages): array
    {
        return $messages
            ->map(fn (ChatMessage $message): array => [
                'target_type' => ChatMessage::class,
                'target_id' => (int) $message->id,
                'target_author_id' => (int) $message->sender_user_id,
                'content_snapshot' => $this->messageSnapshot($message),
            ])
            ->values()
            ->all();
    }

    private function messageSnapshot(ChatMessage $message): string
    {
        return sprintf(
            '#%d %s %s: %s',
            (int) $message->id,
            $message->created_at?->toISOString() ?? '-',
            $this->senderLabel($message),
            $this->messageBody($message),
        );
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
