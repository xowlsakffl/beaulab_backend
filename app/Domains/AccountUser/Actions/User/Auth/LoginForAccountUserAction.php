<?php

namespace App\Domains\AccountUser\Actions\User\Auth;

use App\Domains\AccountUser\Dto\User\Auth\AuthForAccountUserDto;
use App\Domains\AccountUser\Queries\User\Auth\LoginForAccountUserQuery;
use Illuminate\Support\Facades\Log;

/**
 * 앱 사용자 로그인 유스케이스.
 * 인증/토큰 발급은 Query에 위임하고 API 응답 DTO를 구성한다.
 */
final class LoginForAccountUserAction
{
    public function __construct(
        private readonly LoginForAccountUserQuery $query,
    ) {}

    /**
     * @param array{email:string,password:string,device_name?:string|null} $filters
     * @return array{token:string, actor:string, user: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(array $filters): array
    {
        Log::info('앱 사용자 로그인', [
            'email' => $filters['email'] ?? null,
        ]);

        $result = $this->query->login($filters);

        return [
            'token' => $result['token'],
            'actor' => 'user',
            'user' => AuthForAccountUserDto::fromModel($result['user'])->toArray(),
            'roles' => $result['roles'],
            'permissions' => $result['permissions'],
        ];
    }
}
