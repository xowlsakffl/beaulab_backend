<?php

namespace App\Domains\Hospital\Policies\Hospital;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Hospital\Models\Hospital;

/**
 * HospitalForHospitalPolicy 역할 정의.
 * 병원 도메인의 권한 정책으로, 현재 actor가 이 리소스에 수행할 수 있는 작업인지 판단하는 권한 규칙을 정의한다.
 */
final class HospitalForHospitalPolicy
{
    public function viewAny(AccountHospital $actor): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_PROFILE_SHOW);
    }

    public function view(AccountHospital $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_PROFILE_SHOW) && $this->ownsHospital($actor, $hospital);
    }

    public function create(AccountHospital $actor): bool
    {
        return false;
    }

    public function update(AccountHospital $actor, Hospital $hospital): bool
    {
        return $actor->can(AccessPermissions::HOSPITAL_PROFILE_UPDATE) && $this->ownsHospital($actor, $hospital);
    }

    public function delete(AccountHospital $actor, Hospital $hospital): bool
    {
        return false;
    }

    private function ownsHospital(AccountHospital $actor, Hospital $hospital): bool
    {
        return (int) $actor->hospital_id > 0 && (int) $actor->hospital_id === (int) $hospital->id;
    }
}
