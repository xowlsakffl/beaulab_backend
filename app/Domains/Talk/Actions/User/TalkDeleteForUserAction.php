<?php

namespace App\Domains\Talk\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
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

            if ((string) $lockedTalk->status === Talk::STATUS_INACTIVE) {
                return $lockedTalk->fresh([
                    'author',
                    'categories',
                    'images',
                    'poll.options',
                ]);
            }

            $updatedTalk = $this->query->markDeleted(
                $lockedTalk,
                Talk::STATUS_INACTIVE,
            );

            $this->historyCreateAction->execute(
                target: $updatedTalk,
                action: OperationHistory::ACTION_STATE_UPDATED,
                actor: $user,
                reason: '본인삭제',
                metadata: [
                    'source' => 'user.talk.status',
                ],
                changes: OperationHistoryChangeSetBuilder::single(
                    key: 'status',
                    label: '공개여부',
                    before: $beforeStatus,
                    after: Talk::STATUS_INACTIVE,
                    beforeDisplay: $beforeStatus === Talk::STATUS_ACTIVE ? '노출' : '미노출',
                    afterDisplay: '미노출',
                ),
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
