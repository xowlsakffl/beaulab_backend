<?php

namespace App\Domains\AccountBeauty\Actions\Auth;

use App\Domains\AccountBeauty\Dto\Auth\ProfileForAccountBeautyDto;
use App\Domains\AccountBeauty\Models\AccountBeauty;

final class GetMyProfileForAccountBeautyAction
{
    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountBeauty $beauty): array
    {
        return [
            'profile' => ProfileForAccountBeautyDto::fromModel($beauty)->toArray(),
            'roles' => $beauty->getRoleNames()->values()->all(),
            'permissions' => $beauty->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
