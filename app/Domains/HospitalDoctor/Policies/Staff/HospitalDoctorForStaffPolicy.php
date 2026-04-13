<?php

namespace App\Domains\HospitalDoctor\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;

/**
 * HospitalDoctorForStaffPolicy 역할 정의.
 * 병원 의사 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class HospitalDoctorForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_SHOW);
    }

    public function view(AccountStaff $actor, HospitalDoctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_SHOW);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_CREATE);
    }

    public function update(AccountStaff $actor, HospitalDoctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalDoctor $doctor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_DOCTOR_DELETE);
    }
}
