<?php

namespace App\Domains\AccountBeauty\Actions\Beauty;

use App\Common\Auth\ActorAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 뷰티 계정 로그아웃 유스케이스.
 * 현재 브라우저의 뷰티 세션을 폐기한다.
 */
final class LogoutForAccountBeautyAction
{
    public function __construct(
        private readonly ActorAuthentication $authentication,
    ) {}

    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        $this->authentication->logout($actor);

        Log::info('뷰티 로그아웃', [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getAuthIdentifier(),
        ]);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
