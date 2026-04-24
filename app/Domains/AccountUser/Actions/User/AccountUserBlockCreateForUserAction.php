<?php

namespace App\Domains\AccountUser\Actions\User;

use App\Domains\AccountUser\Dto\User\AccountUserBlockForUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\AccountUserBlockCreateForUserQuery;
use App\Domains\Chat\Queries\User\ChatHideForUserBlockQuery;
use Illuminate\Support\Facades\DB;

/**
 * 사용자 차단 생성 유스케이스.
 * 차단 대상 조회와 차단 생성은 Query에 위임하고 Action은 단건 응답 구조를 조합한다.
 */
final class AccountUserBlockCreateForUserAction
{
    public function __construct(
        private readonly AccountUserBlockCreateForUserQuery $query,
        private readonly ChatHideForUserBlockQuery $chatHideQuery,
    ) {}

    public function execute(AccountUser $user, int $blockedUserId): array
    {
        $block = DB::transaction(function () use ($user, $blockedUserId) {
            $block = $this->query->create($user, $blockedUserId);

            $this->chatHideQuery->hideForBlocker((int) $user->id, (int) $block->blocked_user_id);

            return $block->load('blocked:id,nickname,email,status');
        });

        return [
            'block' => AccountUserBlockForUserDto::fromModel($block)->toArray(),
        ];
    }
}
