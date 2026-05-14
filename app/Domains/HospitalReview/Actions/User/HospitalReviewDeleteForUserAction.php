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

            if ((string) $lockedReview->status === HospitalReview::STATUS_INACTIVE) {
                return $lockedReview->fresh([
                    'author',
                    'categories',
                    'beforeImages',
                    'afterImages',
                ]);
            }

            $updatedReview = $this->query->markDeleted(
                $lockedReview,
                HospitalReview::STATUS_INACTIVE,
            );

            $this->historyCreateAction->execute(
                target: $updatedReview,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'status',
                beforeValue: $beforeStatus,
                afterValue: HospitalReview::STATUS_INACTIVE,
                reason: '본인삭제',
                metadata: [
                    'before_label' => $beforeStatus === HospitalReview::STATUS_ACTIVE ? '노출' : '미노출',
                    'after_label' => '미노출',
                    'source' => 'user.hospital_review.status',
                ],
            );

            return $updatedReview->fresh([
                'author',
                'categories',
                'beforeImages',
                'afterImages',
            ]);
        });

        return [
            'hospital_review' => HospitalReviewForUserDetailDto::fromModel($review)->toArray(),
        ];
    }
}
