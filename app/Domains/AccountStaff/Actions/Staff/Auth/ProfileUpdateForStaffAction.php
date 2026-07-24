<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Domains\AccountStaff\Dto\Staff\AccountStaffForStaffDto;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Staff\Auth\ProfileUpdateForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class ProfileUpdateForStaffAction
{
    public function __construct(
        private readonly ProfileUpdateForStaffQuery $query,
    ) {}

    /**
     * @return array{profile: array}
     */
    public function execute(AccountStaff $staff, array $filters): array
    {
        Gate::authorize('updateProfile', $staff);

        Log::info('뷰랩 직원 프로필 수정', [
            'staff_id' => $staff->id,
            'keys' => array_keys($filters),
        ]);

        $staff = $this->query->update($staff, $filters);

        return [
            'profile' => AccountStaffForStaffDto::fromModel($staff)->toArray(),
        ];
    }
}
