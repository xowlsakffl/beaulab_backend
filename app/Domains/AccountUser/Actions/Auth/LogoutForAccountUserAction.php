<?php

namespace App\Domains\AccountUser\Actions\Auth;

use App\Domains\AccountUser\Queries\Auth\LogoutForAccountUserQuery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

final class LogoutForAccountUserAction
{
    public function __construct(
        private readonly LogoutForAccountUserQuery $query,
    ) {}

    /**
     * @return array{message:string}
     */
    public function execute(?Authenticatable $actor): array
    {
        Log::info('앱 사용자 로그아웃', [
            'actor_type' => $actor ? get_class($actor) : null,
            'actor_id' => $actor?->getAuthIdentifier(),
        ]);

        $this->query->deleteCurrentToken($actor);

        return [
            'message' => '로그아웃됨',
        ];
    }
}
