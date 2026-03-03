<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

final class LogoutForAccountBeautyAction
{
    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('파트너 로그아웃', [
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getAuthIdentifier(),
        ]);

        $actor->currentAccessToken()?->delete();

        return [
            'message' => '로그아웃됨',
        ];
    }
}
