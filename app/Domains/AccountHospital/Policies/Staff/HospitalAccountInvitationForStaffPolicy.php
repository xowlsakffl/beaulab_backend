<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use App\Domains\AccountStaff\Models\AccountStaff;

final class HospitalAccountInvitationForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_SHOW);
    }

    public function view(AccountStaff $actor, HospitalAccountInvitation $invitation): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_SHOW)
            && $this->canViewSource($actor, $invitation);
    }

    public function create(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_UPDATE);
    }

    public function delete(AccountStaff $actor, HospitalAccountInvitation $invitation): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ACCOUNT_INVITATION_UPDATE)
            && $this->canViewSource($actor, $invitation);
    }

    private function canViewSource(AccountStaff $actor, HospitalAccountInvitation $invitation): bool
    {
        return match ($invitation->source_type) {
            HospitalAccountInvitation::SOURCE_HOSPITAL => $actor->can(AccessPermissions::BEAULAB_HOSPITAL_SHOW),
            HospitalAccountInvitation::SOURCE_HOSPITAL_ENTRY => $actor->can(AccessPermissions::BEAULAB_HOSPITAL_ENTRY_SHOW),
            default => false,
        };
    }
}
