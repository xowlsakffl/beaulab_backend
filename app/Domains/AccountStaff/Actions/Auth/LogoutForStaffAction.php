<?php

namespace App\Domains\AccountStaff\Actions\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

final class LogoutForStaffAction
{
    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('스태프 로그아웃', [
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getAuthIdentifier(),
        ]);

        // 현재 토큰만 삭제
        $actor->currentAccessToken()?->delete();

        return [
            'message' => '로그아웃됨',
        ];
    }
}
