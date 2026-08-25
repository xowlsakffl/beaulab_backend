<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountHospital\Dto\Staff\HospitalAccountInvitationForStaffDto;
use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class HospitalAccountInvitationRevokeForStaffAction
{
    /** @return array{invitation: array, message: string} */
    public function execute(HospitalAccountInvitation $invitation): array
    {
        Gate::authorize('delete', $invitation);

        $updated = DB::transaction(function () use ($invitation): HospitalAccountInvitation {
            $locked = HospitalAccountInvitation::query()
                ->lockForUpdate()
                ->findOrFail($invitation->getKey());

            if ($locked->used_at !== null) {
                throw new CustomException(
                    ErrorCode::INVALID_REQUEST,
                    '이미 사용된 초대는 폐기할 수 없습니다.',
                );
            }

            if ($locked->revoked_at === null) {
                $locked->forceFill(['revoked_at' => now()])->save();
            }

            return $locked->fresh(['createdByStaff:id,name']);
        });

        Log::info('병의원 계정 초대 폐기', [
            'invitation_id' => $updated->getKey(),
            'staff_id' => auth()->id(),
        ]);

        return [
            'invitation' => HospitalAccountInvitationForStaffDto::fromModel($updated),
            'message' => '계정 생성 초대를 폐기했습니다.',
        ];
    }
}
