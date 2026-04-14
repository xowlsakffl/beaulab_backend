<?php

namespace App\Domains\AccountStaff\Actions\Staff\Auth;

use App\Domains\AccountStaff\Dto\Staff\ProfileForStaffDto;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Staff\Auth\UpdateProfileForStaffQuery;
use Illuminate\Support\Facades\Log;

/**
 * 스태프 프로필 수정 유스케이스.
 * 저장은 Query에 위임하고 변경된 프로필 DTO를 반환한다.
 */
final class UpdateProfileForStaffAction
{
    public function __construct(
        private readonly UpdateProfileForStaffQuery $query,
    ) {}

    /**
     * @return array{profile: array}
     */
    public function execute(AccountStaff $staff, array $filters): array
    {
        Log::info('뷰랩 직원 프로필 수정', [
            'staff_id' => $staff->id,
            'keys' => array_keys($filters),
        ]);

        $staff = $this->query->update($staff, $filters);

        return [
            'profile' => ProfileForStaffDto::fromModel($staff)->toArray(),
        ];
    }
}
