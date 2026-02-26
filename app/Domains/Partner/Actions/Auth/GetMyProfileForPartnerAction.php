<?php

namespace App\Domains\Partner\Actions\Auth;

use App\Domains\Partner\Dto\Auth\ProfileForPartnerDto;
use App\Domains\Partner\Models\AccountPartner;

final class GetMyProfileForPartnerAction
{
    /**
     * @return array{profile: array, roles: list<string>, permissions: list<string>}
     */
    public function execute(AccountPartner $partner): array
    {
        return [
            'profile' => ProfileForPartnerDto::fromModel($partner)->toArray(),
            'roles' => $partner->getRoleNames()->values()->all(),
            'permissions' => $partner->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }
}
