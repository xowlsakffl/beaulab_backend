<?php

namespace App\Domains\AccountUser\Actions\User;

use App\Domains\AccountUser\Dto\User\AccountUserBlockForUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\AccountUserBlockForUserQuery;

/**
 * 사용자 차단 생성 유스케이스.
 * 차단 대상 조회와 차단 생성은 Query에 위임하고 Action은 단건 응답 구조를 조합한다.
 */
final class AccountUserBlockCreateForUserAction
{
    public function __construct(
        private readonly AccountUserBlockForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, int $blockedUserId): array
    {
        $blocked = $this->query->findTarget($blockedUserId);
        $block = $this->query->block($user, $blocked);

        return [
            'block' => AccountUserBlockForUserDto::fromModel($block)->toArray(),
        ];
    }
}
