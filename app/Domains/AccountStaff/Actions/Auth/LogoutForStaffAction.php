<?php

namespace App\Domains\AccountStaff\Actions\Auth;

use App\Domains\AccountStaff\Queries\Auth\LogoutForStaffQuery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 로그아웃 유스케이스.
 * 현재 요청에 사용된 access token만 삭제한다.
 */
final class LogoutForStaffAction
{
    public function __construct(
        private readonly LogoutForStaffQuery $query,
    ) {}

    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('스태프 로그아웃', [
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getAuthIdentifier(),
        ]);

        $this->query->deleteCurrentToken($actor);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
