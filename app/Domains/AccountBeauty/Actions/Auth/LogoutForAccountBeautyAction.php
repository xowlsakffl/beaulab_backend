<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use App\Domains\AccountBeauty\Queries\Auth\LogoutForAccountBeautyQuery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 뷰티 계정 로그아웃 유스케이스.
 * 현재 요청에 사용된 access token만 삭제한다.
 */
final class LogoutForAccountBeautyAction
{
    public function __construct(
        private readonly LogoutForAccountBeautyQuery $query,
    ) {}

    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('뷰티 로그아웃', [
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getAuthIdentifier(),
        ]);

        $this->query->deleteCurrentToken($actor);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
