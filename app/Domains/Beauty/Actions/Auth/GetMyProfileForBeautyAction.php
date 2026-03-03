<?php

namespace App\Domains\Beauty\Actions\Auth;

use App\Domains\Beauty\Dto\Auth\ProfileForBeautyDto;
use App\Domains\Beauty\Models\AccountBeauty;

final class GetMyProfileForBeautyAction
{
    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountBeauty $beauty): array
    {
        return [
            'profile' => ProfileForBeautyDto::fromModel($beauty)->toArray(),
            'roles' => $beauty->getRoleNames()->values()->all(),
            'permissions' => $beauty->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
