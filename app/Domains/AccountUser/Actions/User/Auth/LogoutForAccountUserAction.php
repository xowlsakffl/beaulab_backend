<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Domains\AccountUser\Queries\User\Auth\LogoutForAccountUserQuery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 앱 사용자 로그아웃 유스케이스.
 * 현재 access token만 삭제해 다른 기기 세션은 유지한다.
 */
final class LogoutForAccountUserAction
{
    public function __construct(
        private readonly LogoutForAccountUserQuery $query,
    ) {}

    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('앱 사용자 로그아웃', [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getAuthIdentifier(),
        ]);

        $this->query->deleteCurrentToken($actor);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
