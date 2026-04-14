<?php

namespace App\Domains\AccountStaff\Actions\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Queries\Staff\HospitalOwnerCreateForStaffQuery;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\Auth;

/**
 * HospitalOwnerCreateForStaffAction 역할 정의.
 * 스태프 계정 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalOwnerCreateForStaffAction
{
    public function __construct(
        private readonly HospitalOwnerCreateForStaffQuery $query,
    ) {}

    public function execute(Hospital $hospital, array $payload): AccountHospital
    {
        $owner = $this->query->create([
            'name' => $payload['owner_nickname'],
            'nickname' => $payload['owner_nickname'],
            'email' => mb_strtolower((string) $payload['owner_email']),
            'password' => $payload['owner_password'],
            'hospital_id' => $hospital->id,
            'status' => AccountHospital::STATUS_ACTIVE,
        ]);

        $owner->assignRole(AccessRoles::HOSPITAL_OWNER);

        $permissions = [
            ...AccessPermissions::common(),
            ...AccessPermissions::hospital(),
        ];
        $owner->syncPermissions($permissions);

        activity('audit')
            ->causedBy(Auth::guard('staff')->user())
            ->performedOn($owner)
            ->event('permission_changed')
            ->withProperties([
                'role' => AccessRoles::HOSPITAL_OWNER,
                'permissions' => $permissions,
                'hospital_id' => $owner->hospital_id,
            ])
            ->log('partner owner permissions synced');

        return $owner;
    }
}
