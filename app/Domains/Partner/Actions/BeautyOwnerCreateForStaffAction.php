<?php

namespace App\Domains\Partner\Actions;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Partner\Queries\BeautyOwnerCreateForStaffQuery;
use App\Domains\Partner\Models\AccountPartner;

final class BeautyOwnerCreateForStaffAction
{
    public function __construct(
        private readonly BeautyOwnerCreateForStaffQuery $query,
    ) {}

    public function execute(Beauty $beauty, array $payload): AccountPartner
    {
        $owner = $this->query->create([
            'name' => $payload['owner_nickname'],
            'nickname' => $payload['owner_nickname'],
            'email' => mb_strtolower((string) $payload['owner_email']),
            'password' => $payload['owner_password'],
            'partner_type' => AccountPartner::PARTNER_BEAUTY,
            'beauty_id' => $beauty->id,
            'status' => AccountPartner::STATUS_ACTIVE,
        ]);

        $owner->assignRole(AccessRoles::BEAUTY_OWNER);
        $owner->syncPermissions([
            ...AccessPermissions::common(),
            ...AccessPermissions::beauty(),
        ]);

        return $owner;
    }
}
