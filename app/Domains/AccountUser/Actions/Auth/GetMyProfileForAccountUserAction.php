<?php

namespace App\Domains\AccountUser\Actions\Auth;

use App\Domains\AccountUser\Dto\Auth\ProfileForAccountUserDto;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\AccountUser\Queries\Auth\ProfileForAccountUserQuery;

final class GetMyProfileForAccountUserAction
{
    public function __construct(
        private readonly ProfileForAccountUserQuery $query,
    ) {}

    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountUser $user): array
    {
        $authorization = $this->query->authorizationSnapshot($user);

        return [
            'profile' => ProfileForAccountUserDto::fromModel($user)->toArray(),
            'roles' => $authorization['roles'],
            'permissions' => $authorization['permissions'],
        ];
    }
}
