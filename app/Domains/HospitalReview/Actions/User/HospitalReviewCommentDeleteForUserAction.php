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

            if ((string) $lockedComment->status === HospitalReviewComment::STATUS_INACTIVE) {
                return $lockedComment->fresh(['author', 'mentions.mentionedUser']);
            }

            $updatedComment = $this->query->markDeleted(
                $lockedComment,
                HospitalReviewComment::STATUS_INACTIVE,
            );

            $this->historyCreateAction->execute(
                target: $updatedComment,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'status',
                beforeValue: $beforeStatus,
                afterValue: HospitalReviewComment::STATUS_INACTIVE,
                reason: '본인삭제',
                metadata: [
                    'before_label' => $beforeStatus === HospitalReviewComment::STATUS_ACTIVE ? '노출' : '미노출',
                    'after_label' => '미노출',
                    'source' => 'user.hospital_review_comment.status',
                ],
            );

            return $updatedComment->fresh(['author', 'mentions.mentionedUser']);
        });

        return [
            'comment' => HospitalReviewCommentForUserDto::fromModel($comment)->toArray(),
        ];
    }
}
