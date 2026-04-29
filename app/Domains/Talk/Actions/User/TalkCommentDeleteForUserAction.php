<?php

namespace App\Domains\Talk\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Talk\Dto\User\TalkCommentForUserDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Queries\User\TalkCommentDeleteForUserQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class TalkCommentDeleteForUserAction
{
    public function __construct(
        private readonly TalkCommentDeleteForUserQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(AccountUser $user, Talk $talk, TalkComment $comment): array
    {
        Gate::authorize('delete', $comment);

        $comment = DB::transaction(function () use ($user, $talk, $comment): TalkComment {
            $lockedComment = $this->query->getOwnedForUpdate((int) $talk->id, (int) $comment->id, (int) $user->id);

            if (! $lockedComment instanceof TalkComment) {
                throw new CustomException(ErrorCode::FORBIDDEN, '본인이 작성한 토크 댓글만 삭제할 수 있습니다.');
            }

            $beforeStatus = (string) $lockedComment->status;
            $beforePostStatus = (string) $lockedComment->post_status;

            if ($beforePostStatus === TalkComment::POST_STATUS_USER_DELETE) {
                return $lockedComment->fresh(['author']);
            }

            if ($lockedComment->isStatusChangeLocked()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '현재 상태의 토크 댓글은 삭제할 수 없습니다.');
            }

            $updatedComment = $this->query->markDeleted(
                $lockedComment,
                TalkComment::STATUS_INACTIVE,
                TalkComment::POST_STATUS_USER_DELETE,
            );

            if ($beforeStatus !== TalkComment::STATUS_INACTIVE) {
                $this->historyCreateAction->execute(
                    target: $updatedComment,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $user,
                    field: 'status',
                    beforeValue: $beforeStatus,
                    afterValue: TalkComment::STATUS_INACTIVE,
                    metadata: [
                        'before_label' => $beforeStatus,
                        'after_label' => TalkComment::STATUS_INACTIVE,
                        'source' => 'user.talk_comment.status',
                    ],
                );
            }

            $this->historyCreateAction->execute(
                target: $updatedComment,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'post_status',
                beforeValue: $beforePostStatus,
                afterValue: TalkComment::POST_STATUS_USER_DELETE,
                metadata: [
                    'before_label' => $beforePostStatus,
                    'after_label' => TalkComment::POST_STATUS_USER_DELETE,
                    'source' => 'user.talk_comment.post_status',
                ],
            );

            return $updatedComment->fresh(['author']);
        });

        return [
            'comment' => TalkCommentForUserDto::fromModel($comment)->toArray(),
        ];
    }
}
