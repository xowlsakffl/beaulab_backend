<?php

namespace App\Domains\Talk\Queries\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkSave;
use Illuminate\Support\Facades\DB;

/**
 * 사용자 토크 저장 DB 쿼리.
 */
final class TalkSaveForUserQuery
{
    public function save(AccountUser $user, Talk $talk): Talk
    {
        return DB::transaction(function () use ($user, $talk): Talk {
            $lockedTalk = $this->lockVisibleActiveTalk($talk);

            $save = TalkSave::query()->firstOrCreate([
                'talk_id' => $lockedTalk->id,
                'account_user_id' => $user->id,
            ]);

            if ($save->wasRecentlyCreated) {
                $lockedTalk->forceFill([
                    'save_count' => (int) $lockedTalk->save_count + 1,
                ])->save();
            }

            return $lockedTalk->fresh();
        });
    }

    public function unsave(AccountUser $user, Talk $talk): Talk
    {
        return DB::transaction(function () use ($user, $talk): Talk {
            $lockedTalk = Talk::query()
                ->whereKey($talk->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedTalk instanceof Talk) {
                throw new CustomException(ErrorCode::NOT_FOUND);
            }

            $deleted = TalkSave::query()
                ->where('talk_id', $lockedTalk->id)
                ->where('account_user_id', $user->id)
                ->delete();

            if ($deleted > 0) {
                $lockedTalk->forceFill([
                    'save_count' => max(0, (int) $lockedTalk->save_count - 1),
                ])->save();
            }

            return $lockedTalk->fresh();
        });
    }

    private function lockVisibleActiveTalk(Talk $talk): Talk
    {
        $lockedTalk = Talk::query()
            ->whereKey($talk->id)
            ->where('status', Talk::STATUS_ACTIVE)
            ->where('is_visible', true)
            ->lockForUpdate()
            ->first();

        if (! $lockedTalk instanceof Talk) {
            throw new CustomException(ErrorCode::NOT_FOUND);
        }

        return $lockedTalk;
    }
}
