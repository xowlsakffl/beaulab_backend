<?php

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Common\Auth\ActorAuthentication;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 로그아웃 유스케이스.
 * 현재 브라우저의 병의원 세션을 폐기한다.
 */
final class LogoutForAccountHospitalAction
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

        Log::info('병원 로그아웃', [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getAuthIdentifier(),
        ]);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
