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

            if ((string) $lockedComment->status === TalkComment::STATUS_INACTIVE) {
                return $lockedComment->fresh(['author', 'mentions.mentionedUser']);
            }

            $updatedComment = $this->query->markDeleted(
                $lockedComment,
                TalkComment::STATUS_INACTIVE,
            );

            $this->historyCreateAction->execute(
                target: $updatedComment,
                action: OperationHistory::ACTION_STATUS_UPDATED,
                actor: $user,
                field: 'status',
                beforeValue: $beforeStatus,
                afterValue: TalkComment::STATUS_INACTIVE,
                reason: '본인삭제',
                metadata: [
                    'before_label' => $beforeStatus === TalkComment::STATUS_ACTIVE ? '노출' : '미노출',
                    'after_label' => '미노출',
                    'source' => 'user.talk_comment.status',
                ],
            );

            return $updatedComment->fresh(['author', 'mentions.mentionedUser']);
        });

        return [
            'comment' => TalkCommentForUserDto::fromModel($comment)->toArray(),
        ];
    }
}
