<?php

namespace App\Domains\Talk\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Dto\User\TalkSaveForUserDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\User\TalkSaveForUserQuery;

/**
 * 사용자 토크 저장/저장 해제 유스케이스.
 */
final class TalkSaveForUserAction
{
    public function __construct(
        private readonly TalkSaveForUserQuery $query,
    ) {}

    public function save(AccountUser $user, Talk $talk): array
    {
        $talk = $this->query->save($user, $talk);

        return [
            'save' => TalkSaveForUserDto::fromTalk($talk, true)->toArray(),
        ];
    }

    public function unsave(AccountUser $user, Talk $talk): array
    {
        $talk = $this->query->unsave($user, $talk);

        return [
            'save' => TalkSaveForUserDto::fromTalk($talk, false)->toArray(),
        ];
    }
}
