<?php

namespace App\Domains\HospitalReview\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalReview\Dto\User\HospitalReviewCommentForUserDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Queries\User\HospitalReviewCommentDeleteForUserQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewCommentDeleteForUserAction
{
    public function __construct(
        private readonly HospitalReviewCommentDeleteForUserQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(AccountUser $user, HospitalReview $review, HospitalReviewComment $comment): array
    {
        Gate::authorize('delete', $comment);

        $comment = DB::transaction(function () use ($user, $review, $comment): HospitalReviewComment {
            $lockedComment = $this->query->getOwnedForUpdate((int) $review->id, (int) $comment->id, (int) $user->id);

            if (! $lockedComment instanceof HospitalReviewComment) {
                throw new CustomException(ErrorCode::FORBIDDEN, '본인이 작성한 병의원 후기 댓글만 삭제할 수 있습니다.');
            }

            $beforeStatus = (string) $lockedComment->status;
            $beforePostStatus = (string) $lockedComment->post_status;

            if ($beforePostStatus === HospitalReviewComment::POST_STATUS_USER_DELETE) {
                return $lockedComment->fresh(['author', 'mentions.mentionedUser']);
            }

            if ($lockedComment->isStatusChangeLocked()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '현재 상태의 병의원 후기 댓글은 삭제할 수 없습니다.');
            }

            $updatedComment = $this->query->markDeleted(
                $lockedComment,
                HospitalReviewComment::STATUS_INACTIVE,
                HospitalReviewComment::POST_STATUS_USER_DELETE,
            );

            if ($beforeStatus !== HospitalReviewComment::STATUS_INACTIVE) {
                $this->historyCreateAction->execute(
                    target: $updatedComment,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $user,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: HospitalReviewComment::STATUS_INACTIVE,
                    metadata: [
                        'before_label' => $beforeStatus,
                        'after_label' => HospitalReviewComment::STATUS_INACTIVE,
                        'source' => 'user.hospital_review_comment.status',
                    ],
                );
            }

            $this->historyCreateAction->execute(
                target: $updatedComment,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'post_status',
                beforeValue: $beforePostStatus,
                afterValue: HospitalReviewComment::POST_STATUS_USER_DELETE,
                metadata: [
                    'before_label' => $beforePostStatus,
                    'after_label' => HospitalReviewComment::POST_STATUS_USER_DELETE,
                    'source' => 'user.hospital_review_comment.post_status',
                ],
            );

            return $updatedComment->fresh(['author', 'mentions.mentionedUser']);
        });

        return [
            'comment' => HospitalReviewCommentForUserDto::fromModel($comment)->toArray(),
        ];
    }
}
