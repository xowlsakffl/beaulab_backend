<?php

namespace App\Domains\Partner\Actions;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Partner\Queries\BeautyOwnerCreateForStaffQuery;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use Illuminate\Support\Facades\Auth;

final class BeautyOwnerCreateForStaffAction
{
    public function __construct(
        private readonly BeautyOwnerCreateForStaffQuery $query,
    ) {}

    public function execute(Beauty $beauty, array $payload): AccountBeauty
    {
        $owner = $this->query->create([
            'name' => $payload['owner_nickname'],
            'nickname' => $payload['owner_nickname'],
            'email' => mb_strtolower((string) $payload['owner_email']),
            'password' => $payload['owner_password'],
            'beauty_id' => $beauty->id,
            'status' => AccountBeauty::STATUS_ACTIVE,
        ]);

        $owner->assignRole(AccessRoles::BEAUTY_OWNER);

        $permissions = [
            ...AccessPermissions::common(),
            ...AccessPermissions::beauty(),
        ];
        $owner->syncPermissions($permissions);

        activity('audit')
            ->causedBy(Auth::guard('staff')->user())
            ->performedOn($owner)
            ->event('permission_changed')
            ->withProperties([
                'role' => AccessRoles::BEAUTY_OWNER,
                'permissions' => $permissions,
                'partner_type' => $owner->partner_type,
                'beauty_id' => $owner->beauty_id,
            ])
            ->log('partner owner permissions synced');

        return $owner;
    }
}