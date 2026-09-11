<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Actions\HospitalAccountPasswordResetSendAction;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Support\Facades\Gate;

final class HospitalAccountPasswordResetSendForStaffAction
{
    public function __construct(private readonly HospitalAccountPasswordResetSendAction $send) {}

    public function execute(AccountStaff $actor, Hospital $hospital, ?string $recipientEmail = null): array
    {
        Gate::forUser($actor)->authorize('sendPasswordResetLink', $hospital);
        $id = $hospital->accountHospital?->getKey();
        if ($id === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '연결된 병의원 계정이 없습니다.');
        }

        return $this->send->execute((int) $id, (int) $actor->getKey(), recipientEmail: $recipientEmail);
    }
}
