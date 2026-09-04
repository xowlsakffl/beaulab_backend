<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Common\Auth\ActorAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 사용자 로그아웃 유스케이스.
 * 현재 웹 세션 또는 앱 토큰만 폐기해 다른 기기의 로그인을 유지한다.
 */
final class LogoutForAccountUserAction
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

        Log::info('앱 사용자 로그아웃', [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getAuthIdentifier(),
        ]);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
