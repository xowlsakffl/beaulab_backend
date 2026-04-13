<?php

namespace App\Domains\AccountUser\Actions\Auth;

use App\Domains\AccountUser\Dto\Auth\AuthForAccountUserDto;
use App\Domains\AccountUser\Queries\Auth\LoginForAccountUserQuery;
use Illuminate\Support\Facades\Log;

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
