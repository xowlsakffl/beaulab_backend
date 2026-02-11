<?php

namespace App\Domains\Staff\Actions\Auth;

use App\Domains\Staff\Dto\Auth\ProfileForStaffDto;
use App\Domains\Staff\Models\AccountStaff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UpdateProfileForStaffAction
{
    /**
     * @return array{profile: array}
     */
    public function execute(AccountStaff $staff, array $filters): array
    {
        Log::info('뷰랩 직원 프로필 수정', [
            'staff_id' => $staff->id,
            'keys' => array_keys($filters),
        ]);

        $staff = DB::transaction(function () use ($staff, $filters) {
            $staff->fill($filters)->save();
            return $staff->fresh();
        });

        return [
            'profile' => ProfileForStaffDto::fromModel($staff)->toArray(),
        ];
    }
}
