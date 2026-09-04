<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Common\Auth\ActorAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 로그아웃 유스케이스.
 * 현재 브라우저의 스태프 세션을 폐기한다.
 */
final class LogoutForStaffAction
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

        Log::info('스태프 로그아웃', [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getAuthIdentifier(),
        ]);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
