<?php

namespace App\Domains\AccountUser\Actions\User;

use App\Domains\AccountUser\Dto\User\AccountUserBlockForUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\User\AccountUserBlockForUserQuery;

/**
 * 사용자 차단 목록 조회 유스케이스.
 * Query가 페이지네이션과 모델 조회를 담당하고 Action은 목록 응답 형태로 정규화한다.
 */
final class AccountUserBlockListForUserAction
{
    public function __construct(
        private readonly AccountUserBlockForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, array $filters): array
    {
        $paginator = $this->query->paginate($user, $filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($block) => AccountUserBlockForUserDto::fromModel($block)->toArray())
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
}
