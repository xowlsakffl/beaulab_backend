<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use App\Domains\AccountBeauty\Dto\Auth\ProfileForAccountBeautyDto;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountBeauty\Queries\Auth\ProfileForAccountBeautyQuery;

final class GetMyProfileForAccountBeautyAction
{
    public function __construct(
        private readonly ProfileForAccountBeautyQuery $query,
    ) {}

    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountBeauty $beauty): array
    {
        $authorization = $this->query->authorizationSnapshot($beauty);

        return [
            'profile' => ProfileForAccountBeautyDto::fromModel($beauty)->toArray(),
            'roles' => $authorization['roles'],
            'permissions' => $authorization['permissions'],
        ];
    }
}
