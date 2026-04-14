<?php

namespace App\Domains\AccountStaff\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountStaff\Queries\Staff\BeautyOwnerCreateForStaffQuery;
use App\Domains\Beauty\Models\Beauty;
use Illuminate\Support\Facades\Auth;

/**
 * BeautyOwnerCreateForStaffAction 역할 정의.
 * 스태프 계정 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
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
                'beauty_id' => $owner->beauty_id,
            ])
            ->log('partner owner permissions synced');

        return $owner;
    }
}
