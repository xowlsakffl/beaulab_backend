<?php

namespace App\Domains\AccountUser\Actions\User;

use App\Domains\AccountUser\Dto\User\AccountUserBlockForUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\AccountUserBlockForUserQuery;

/**
 * 앱 사용자 차단 유스케이스.
 * Action은 요청 흐름과 응답 DTO 변환만 담당하고 DB 처리는 Query에 위임한다.
 */
final class AccountUserBlockForUserAction
{
    public function __construct(
        private readonly AccountUserBlockForUserQuery $query,
    ) {}

    public function list(AccountUser $user, array $filters): array
    {
        $paginator = $this->query->paginate($user, $filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($block) => AccountUserBlockForUserDto::fromModel($block))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    public function block(AccountUser $user, int $blockedUserId): array
    {
        $blocked = $this->query->findTarget($blockedUserId);
        $block = $this->query->block($user, $blocked);

        return [
            'block' => AccountUserBlockForUserDto::fromModel($block),
        ];
    }

    public function unblock(AccountUser $user, int $blockedUserId): array
    {
        return [
            'unblocked' => $this->query->unblock($user, $blockedUserId) > 0,
        ];
    }
}
