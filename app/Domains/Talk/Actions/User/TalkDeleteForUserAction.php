<?php

namespace App\Domains\Talk\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Talk\Dto\User\TalkForUserDetailDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\User\TalkDeleteForUserQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TalkDeleteForUserAction
{
    public function __construct(
        private readonly TalkDeleteForUserQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(AccountUser $user, Talk $talk): array
    {
        Gate::authorize('delete', $talk);

        $talk = DB::transaction(function () use ($user, $talk): Talk {
            $lockedTalk = $this->query->getOwnedForUpdate((int) $talk->id, (int) $user->id);

            if (! $lockedTalk instanceof Talk) {
                throw new CustomException(ErrorCode::FORBIDDEN, '본인이 작성한 토크만 삭제할 수 있습니다.');
            }

            $beforeStatus = (string) $lockedTalk->status;
            $beforePostStatus = (string) $lockedTalk->post_status;

            if ($beforePostStatus === Talk::POST_STATUS_USER_DELETE) {
                return $lockedTalk->fresh([
                    'author',
                    'categories',
                    'images',
                    'poll.options',
                ]);
            }

            if ($lockedTalk->isStatusChangeLocked()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '현재 상태의 토크는 삭제할 수 없습니다.');
            }

            $updatedTalk = $this->query->markDeleted(
                $lockedTalk,
                Talk::STATUS_INACTIVE,
                Talk::POST_STATUS_USER_DELETE,
            );

            if ($beforeStatus !== Talk::STATUS_INACTIVE) {
                $this->historyCreateAction->execute(
                    target: $updatedTalk,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $user,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: Talk::STATUS_INACTIVE,
                    metadata: [
                        'before_label' => $beforeStatus,
                        'after_label' => Talk::STATUS_INACTIVE,
                        'source' => 'user.talk.status',
                    ],
                );
            }

            $this->historyCreateAction->execute(
                target: $updatedTalk,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'post_status',
                beforeValue: $beforePostStatus,
                afterValue: Talk::POST_STATUS_USER_DELETE,
                metadata: [
                    'before_label' => $beforePostStatus,
                    'after_label' => Talk::POST_STATUS_USER_DELETE,
                    'source' => 'user.talk.post_status',
                ],
            );

            return $updatedTalk->fresh([
                'author',
                'categories',
                'images',
                'poll.options',
            ]);
        });

        return [
            'talk' => TalkForUserDetailDto::fromModel($talk)->toArray(),
        ];
    }
}
