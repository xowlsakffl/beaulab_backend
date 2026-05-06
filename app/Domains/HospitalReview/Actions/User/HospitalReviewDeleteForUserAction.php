<?php

namespace App\Domains\HospitalReview\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalReview\Dto\User\HospitalReviewForUserDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\User\HospitalReviewDeleteForUserQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalReviewDeleteForUserAction
{
    public function __construct(
        private readonly HospitalReviewDeleteForUserQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(AccountUser $user, HospitalReview $review): array
    {
        Gate::authorize('delete', $review);

        $review = DB::transaction(function () use ($user, $review): HospitalReview {
            $lockedReview = $this->query->getOwnedForUpdate((int) $review->id, (int) $user->id);

            if (! $lockedReview instanceof HospitalReview) {
                throw new CustomException(ErrorCode::FORBIDDEN, '본인이 작성한 후기만 삭제할 수 있습니다.');
            }

            $beforeStatus = (string) $lockedReview->status;
            $beforePostStatus = (string) $lockedReview->post_status;

            if ($beforePostStatus === HospitalReview::POST_STATUS_USER_DELETE) {
                return $lockedReview->fresh([
                    'author',
                    'categories',
                    'images',
                ]);
            }

            if ($lockedReview->isStatusChangeLocked()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '현재 상태의 후기는 삭제할 수 없습니다.');
            }

            $updatedReview = $this->query->markDeleted(
                $lockedReview,
                HospitalReview::STATUS_INACTIVE,
                HospitalReview::POST_STATUS_USER_DELETE,
            );

            if ($beforeStatus !== HospitalReview::STATUS_INACTIVE) {
                $this->historyCreateAction->execute(
                    target: $updatedReview,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $user,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: HospitalReview::STATUS_INACTIVE,
                    metadata: [
                        'before_label' => $beforeStatus,
                        'after_label' => HospitalReview::STATUS_INACTIVE,
                        'source' => 'user.hospital_review.status',
                    ],
                );
            }

            $this->historyCreateAction->execute(
                target: $updatedReview,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'post_status',
                beforeValue: $beforePostStatus,
                afterValue: HospitalReview::POST_STATUS_USER_DELETE,
                metadata: [
                    'before_label' => $beforePostStatus,
                    'after_label' => HospitalReview::POST_STATUS_USER_DELETE,
                    'source' => 'user.hospital_review.post_status',
                ],
            );

            return $updatedReview->fresh([
                'author',
                'categories',
                'images',
            ]);
        });

        return [
            'hospital_review' => HospitalReviewForUserDetailDto::fromModel($review)->toArray(),
        ];
    }
}
