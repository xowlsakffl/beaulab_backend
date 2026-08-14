<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Policies\Staff;

use App\Common\Authorization\AccessPermissions;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalWallet\Models\HospitalWallet;

final class HospitalWalletForStaffPolicy
{
    public function viewAny(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW);
    }

    public function view(AccountStaff $actor, HospitalWallet $wallet): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_SHOW);
    }

    public function viewHistory(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_HISTORY_SHOW);
    }

    public function grantService(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_SERVICE_GRANT);
    }

    public function reclaimService(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_SERVICE_RECLAIM);
    }

    public function requestRefund(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_REQUEST);
    }

    public function processRefund(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS);
    }

    public function viewRefundDocuments(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_DOCUMENT_SHOW)
            || $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS);
    }

    public function updateRefundDocuments(AccountStaff $actor): bool
    {
        return $this->viewRefundDocuments($actor)
            && ($actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_REQUEST)
                || $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_REFUND_PROCESS));
    }

    public function sendNotice(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_NOTICE_SEND);
    }

    public function viewNotices(AccountStaff $actor): bool
    {
        return $actor->can(AccessPermissions::BEAULAB_HOSPITAL_WALLET_NOTICE_SHOW);
    }
}
