<?php

namespace App\Domains\Chat\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * 채팅방 생성/재활성화에 필요한 DB 작업을 담당한다.
 * match_key unique 충돌은 동시 생성 경쟁으로 보고 한 번 재시도한다.
 */
final class ChatOpenOrCreateForUserQuery
{
    public function findActivePeer(int $peerUserId): AccountUser
    {
        $peer = AccountUser::query()->find($peerUserId);

        if (! $peer instanceof AccountUser) {
            throw new CustomException(ErrorCode::USER_NOT_FOUND);
        }

        if (! $peer->isActive()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '활성 상태의 사용자와만 채팅할 수 있습니다.');
        }

        return $peer;
    }

    public function openOrCreate(AccountUser $user, AccountUser $peer, string $matchKey): Chat
    {
        try {
            return $this->openOrCreateInTransaction($user, $peer, $matchKey);
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            return $this->openOrCreateInTransaction($user, $peer, $matchKey);
        }
    }

    private function openOrCreateInTransaction(AccountUser $user, AccountUser $peer, string $matchKey): Chat
    {
        return DB::transaction(function () use ($user, $peer, $matchKey): Chat {
            // 같은 두 사용자 사이의 채팅방은 match_key 기준으로 하나만 유지한다.
            $chat = Chat::withTrashed()
                ->where('match_key', $matchKey)
                ->lockForUpdate()
                ->first();

            if (! $chat instanceof Chat) {
                $chat = Chat::create([
                    'status' => Chat::STATUS_ACTIVE,
                    'match_key' => $matchKey,
                    'created_by_user_id' => $user->id,
                ]);

                $chat->participants()->createMany([
                    ['account_user_id' => $user->id],
                    ['account_user_id' => $peer->id],
                ]);
            } else {
                if ($chat->trashed()) {
                    $chat->restore();
                }

                if ($chat->status === Chat::STATUS_SUSPENDED) {
                    throw new CustomException(ErrorCode::INVALID_REQUEST, '정지된 채팅방입니다.');
                }

                if ($chat->status !== Chat::STATUS_ACTIVE) {
                    $chat->forceFill([
                        'status' => Chat::STATUS_ACTIVE,
                        'closed_at' => null,
                    ])->save();
                }

                $chat->participants()->firstOrCreate(['account_user_id' => $user->id]);
                $chat->participants()->firstOrCreate(['account_user_id' => $peer->id]);
            }

            return $chat->fresh([
                'lastMessage.sender:id,name,email',
                'lastMessage.attachments',
                'participants.accountUser:id,name,email',
            ]);
        });
    }
}
