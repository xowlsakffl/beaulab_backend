<?php

namespace App\Domains\AccountHospital\Actions\Hospital;

use App\Domains\AccountHospital\Queries\Hospital\LogoutForAccountHospitalQuery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

/**
 * 병원 계정 로그아웃 유스케이스.
 * 현재 요청에 사용된 access token만 삭제한다.
 */
final class LogoutForAccountHospitalAction
{
    public function __construct(
        private readonly LogoutForAccountHospitalQuery $query,
    ) {}

    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('병원 로그아웃', [
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getAuthIdentifier(),
        ]);

        $this->query->deleteCurrentToken($actor);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
