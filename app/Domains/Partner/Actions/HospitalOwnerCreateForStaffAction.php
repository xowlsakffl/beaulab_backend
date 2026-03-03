<?php

namespace App\Domains\Partner\Actions;

use App\Common\Authorization\AccessPermissions;
use App\Common\Authorization\AccessRoles;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Partner\Queries\HospitalOwnerCreateForStaffQuery;
use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Support\Facades\Auth;

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
                'partner_type' => $owner->partner_type,
                'hospital_id' => $owner->hospital_id,
            ])
            ->log('partner owner permissions synced');

        return $owner;
    }
}