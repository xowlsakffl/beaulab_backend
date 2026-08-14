<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Policies;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\HospitalWallet\Models\HospitalWallet;
use App\Domains\HospitalWallet\Policies\Staff\HospitalWalletForStaffPolicy;

final class HospitalWalletPolicy
{
    public function viewAny(mixed $actor): bool
    {
        return $this->delegate($actor)->viewAny($actor);
    }

    public function view(mixed $actor, HospitalWallet $wallet): bool
    {
        return $this->delegate($actor)->view($actor, $wallet);
    }

    public function viewHistory(mixed $actor): bool
    {
        return $this->delegate($actor)->viewHistory($actor);
    }

    public function grantService(mixed $actor): bool
    {
        return $this->delegate($actor)->grantService($actor);
    }

    public function reclaimService(mixed $actor): bool
    {
        return $this->delegate($actor)->reclaimService($actor);
    }

    public function requestRefund(mixed $actor): bool
    {
        return $this->delegate($actor)->requestRefund($actor);
    }

    public function processRefund(mixed $actor): bool
    {
        return $this->delegate($actor)->processRefund($actor);
    }

    public function updateRefundDocuments(mixed $actor): bool
    {
        return $this->delegate($actor)->updateRefundDocuments($actor);
    }

    public function sendNotice(mixed $actor): bool
    {
        return $this->delegate($actor)->sendNotice($actor);
    }

    public function viewNotices(mixed $actor): bool
    {
        return $this->delegate($actor)->viewNotices($actor);
    }

    private function delegate(mixed $actor): object
    {
        return match (true) {
            $actor instanceof AccountStaff => app(HospitalWalletForStaffPolicy::class),
            default => new class
            {
                public function viewAny(mixed $actor): bool
                {
                    return false;
                }

                public function view(mixed $actor, HospitalWallet $wallet): bool
                {
                    return false;
                }

                public function viewHistory(mixed $actor): bool
                {
                    return false;
                }

                public function grantService(mixed $actor): bool
                {
                    return false;
                }

                public function reclaimService(mixed $actor): bool
                {
                    return false;
                }

                public function requestRefund(mixed $actor): bool
                {
                    return false;
                }

                public function processRefund(mixed $actor): bool
                {
                    return false;
                }

                public function updateRefundDocuments(mixed $actor): bool
                {
                    return false;
                }

                public function sendNotice(mixed $actor): bool
                {
                    return false;
                }

                public function viewNotices(mixed $actor): bool
                {
                    return false;
                }
            },
        };
    }
}
