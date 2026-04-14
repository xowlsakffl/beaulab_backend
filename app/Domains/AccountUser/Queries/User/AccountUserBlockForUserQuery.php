<?php

namespace App\Domains\AccountUser\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Models\AccountUserBlock;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Support\ChatMatchKey;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 앱 사용자 차단 관계 DB 접근 쿼리.
 * 차단/해제와 차단 시 내 채팅방 숨김 처리를 같은 트랜잭션으로 묶는다.
 */
final class AccountUserBlockForUserQuery
{
    public function paginate(AccountUser $user, array $filters): LengthAwarePaginator
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 50));

        return AccountUserBlock::query()
            ->where('blocker_user_id', $user->id)
            ->with('blocked:id,name,email,status')
            ->orderByDesc('blocked_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findTarget(int $blockedUserId): AccountUser
    {
        $blocked = AccountUser::query()->find($blockedUserId);

        if (! $blocked instanceof AccountUser) {
            throw new CustomException(ErrorCode::USER_NOT_FOUND);
        }

        return $blocked;
    }

    public function block(AccountUser $blocker, AccountUser $blocked): AccountUserBlock
    {
        if ((int) $blocker->id === (int) $blocked->id) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인은 차단할 수 없습니다.');
        }

        return DB::transaction(function () use ($blocker, $blocked): AccountUserBlock {
            $now = now();

            AccountUserBlock::query()->upsert(
                [[
                    'blocker_user_id' => $blocker->id,
                    'blocked_user_id' => $blocked->id,
                    'blocked_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['blocker_user_id', 'blocked_user_id'],
                ['updated_at']
            );

            $block = AccountUserBlock::query()
                ->where('blocker_user_id', $blocker->id)
                ->where('blocked_user_id', $blocked->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->hideChatForBlocker((int) $blocker->id, (int) $blocked->id);

            return $block->load('blocked:id,name,email,status');
        });
    }

    public function unblock(AccountUser $blocker, int $blockedUserId): int
    {
        if ((int) $blocker->id === $blockedUserId) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '본인은 차단 해제 대상이 될 수 없습니다.');
        }

        return AccountUserBlock::query()
            ->where('blocker_user_id', $blocker->id)
            ->where('blocked_user_id', $blockedUserId)
            ->delete();
    }

    public function assertCanSendMessage(int $senderUserId, int $peerUserId): void
    {
        $blockerIds = AccountUserBlock::query()
            ->where(function ($query) use ($senderUserId, $peerUserId): void {
                $query
                    ->where('blocker_user_id', $senderUserId)
                    ->where('blocked_user_id', $peerUserId);
            })
            ->orWhere(function ($query) use ($senderUserId, $peerUserId): void {
                $query
                    ->where('blocker_user_id', $peerUserId)
                    ->where('blocked_user_id', $senderUserId);
            })
            ->pluck('blocker_user_id')
            ->map(static fn (mixed $blockerId): int => (int) $blockerId);

        if ($blockerIds->contains($senderUserId)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '차단 해제 후 메시지를 보낼 수 있습니다.');
        }

        if ($blockerIds->contains($peerUserId)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '메시지를 보낼 수 없습니다.');
        }
    }

    private function hideChatForBlocker(int $blockerUserId, int $blockedUserId): void
    {
        $chat = Chat::withTrashed()
            ->where('match_key', ChatMatchKey::forUsers($blockerUserId, $blockedUserId))
            ->lockForUpdate()
            ->first();

        if (! $chat instanceof Chat) {
            return;
        }

        $participant = $chat->participants()
            ->where('account_user_id', $blockerUserId)
            ->lockForUpdate()
            ->first();

        if ($participant === null) {
            return;
        }

        $lastMessageId = $chat->last_message_id ? (int) $chat->last_message_id : null;

        $participant->forceFill([
            'deleted_until_message_id' => $lastMessageId,
            'deleted_at' => now(),
            'last_read_message_id' => $lastMessageId ?? $participant->last_read_message_id,
            'last_read_at' => $lastMessageId !== null ? now() : $participant->last_read_at,
        ])->save();
    }
}
