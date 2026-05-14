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
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'status',
                beforeValue: $beforeStatus,
                afterValue: Talk::STATUS_INACTIVE,
                reason: '본인삭제',
                metadata: [
                    'before_label' => $beforeStatus === Talk::STATUS_ACTIVE ? '노출' : '미노출',
                    'after_label' => '미노출',
                    'source' => 'user.talk.status',
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
