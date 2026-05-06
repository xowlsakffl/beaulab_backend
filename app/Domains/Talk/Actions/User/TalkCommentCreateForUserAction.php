<?php

namespace App\Domains\Talk\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Dto\User\TalkCommentForUserDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Queries\User\TalkCommentCreateForUserQuery;
use Illuminate\Support\Facades\DB;

final class TalkCommentCreateForUserAction
{
    public function __construct(
        private readonly TalkCommentCreateForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, Talk $talk, array $payload): array
    {
        $normalized = [
            'talk_id' => (int) $talk->id,
            'parent_id' => $this->normalizeParentId($payload['parent_id'] ?? null),
            'author_id' => (int) $user->id,
            'content' => (string) $payload['content'],
            'author_ip' => $payload['author_ip'] ?? null,
            'mention' => $this->normalizeMention($payload['mention'] ?? null, $user),
        ];

        $comment = DB::transaction(function () use ($normalized): TalkComment {
            $lockedTalk = $this->query->getTalkForUpdate((int) $normalized['talk_id']);

            if (! $lockedTalk instanceof Talk) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '토크를 찾을 수 없습니다.');
            }

            if ($lockedTalk->status !== Talk::STATUS_ACTIVE || $lockedTalk->post_status !== Talk::POST_STATUS_NORMAL) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '댓글을 작성할 수 없는 토크입니다.');
            }

            $this->assertReplyTargetIsWritable($lockedTalk, $normalized['parent_id']);

            $comment = $this->query->create($normalized);
            $this->createMention($comment, $normalized['mention']);
            $this->query->incrementTalkCommentCount($lockedTalk);

            return $comment->fresh(['author', 'mentions.mentionedUser']);
        });

        return [
            'comment' => TalkCommentForUserDto::fromModel($comment)->toArray(),
        ];
    }

    private function normalizeParentId(mixed $parentId): ?int
    {
        $parentId = (int) $parentId;

        return $parentId > 0 ? $parentId : null;
    }

    private function normalizeMention(mixed $mention, AccountUser $user): ?array
    {
        if (! is_array($mention)) {
            return null;
        }

        $mentionedUserId = (int) ($mention['mentioned_user_id'] ?? 0);
        if ($mentionedUserId <= 0) {
            return null;
        }

        $mentionText = trim((string) $this->query->mentionTextByUserId($mentionedUserId));

        return [
            'mentioned_user_id' => $mentionedUserId,
            'mentioned_by_user_id' => (int) $user->id,
            'mention_text' => $mentionText === '' ? null : $mentionText,
        ];
    }

    private function createMention(TalkComment $comment, ?array $mention): void
    {
        if ($mention === null) {
            return;
        }

        $this->query->createMention($comment, $mention);
    }

    private function assertReplyTargetIsWritable(Talk $talk, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = $this->query->getParentForUpdate((int) $talk->id, $parentId);

        if (! $parent instanceof TalkComment || ! $parent->isRootComment()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '부모 댓글을 확인해 주세요.');
        }

        if ($parent->status !== TalkComment::STATUS_ACTIVE || $parent->post_status !== TalkComment::POST_STATUS_NORMAL) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '답글을 작성할 수 없는 댓글입니다.');
        }
    }
}
