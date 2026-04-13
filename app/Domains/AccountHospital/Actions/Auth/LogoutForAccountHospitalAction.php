<?php

namespace App\Domains\AccountHospital\Actions\Auth;

use App\Domains\AccountHospital\Queries\Auth\LogoutForAccountHospitalQuery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

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
