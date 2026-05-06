<?php

namespace App\Domains\HospitalReview\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalReview\Dto\User\HospitalReviewCommentForUserDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Queries\User\HospitalReviewCommentCreateForUserQuery;
use Illuminate\Support\Facades\DB;

final class HospitalReviewCommentCreateForUserAction
{
    public function __construct(
        private readonly HospitalReviewCommentCreateForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, HospitalReview $review, array $payload): array
    {
        $normalized = [
            'hospital_review_id' => (int) $review->id,
            'parent_id' => $this->normalizeParentId($payload['parent_id'] ?? null),
            'author_id' => (int) $user->id,
            'content' => (string) $payload['content'],
            'author_ip' => $payload['author_ip'] ?? null,
            'mention' => $this->normalizeMention($payload['mention'] ?? null, $user),
        ];

        $comment = DB::transaction(function () use ($normalized): HospitalReviewComment {
            $lockedReview = $this->query->getReviewForUpdate((int) $normalized['hospital_review_id']);

            if (! $lockedReview instanceof HospitalReview) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '병의원 후기를 찾을 수 없습니다.');
            }

            if ($lockedReview->status !== HospitalReview::STATUS_ACTIVE || $lockedReview->post_status !== HospitalReview::POST_STATUS_NORMAL) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '댓글을 작성할 수 없는 병의원 후기입니다.');
            }

            $this->assertReplyTargetIsWritable($lockedReview, $normalized['parent_id']);

            $comment = $this->query->create($normalized);
            $this->createMention($comment, $normalized['mention']);
            $this->query->incrementReviewCommentCount($lockedReview);

            return $comment->fresh(['author', 'mentions.mentionedUser']);
        });

        return [
            'comment' => HospitalReviewCommentForUserDto::fromModel($comment)->toArray(),
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

    private function createMention(HospitalReviewComment $comment, ?array $mention): void
    {
        if ($mention === null) {
            return;
        }

        $this->query->createMention($comment, $mention);
    }

    private function assertReplyTargetIsWritable(HospitalReview $review, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = $this->query->getParentForUpdate((int) $review->id, $parentId);

        if (! $parent instanceof HospitalReviewComment || ! $parent->isRootComment()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '부모 댓글을 확인해 주세요.');
        }

        if ($parent->status !== HospitalReviewComment::STATUS_ACTIVE || $parent->post_status !== HospitalReviewComment::POST_STATUS_NORMAL) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '답글을 작성할 수 없는 댓글입니다.');
        }
    }
}
