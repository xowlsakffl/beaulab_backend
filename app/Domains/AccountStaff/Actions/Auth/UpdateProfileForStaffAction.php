<?php

namespace App\Domains\AccountStaff\Actions\Auth;

use App\Domains\AccountStaff\Dto\Auth\ProfileForStaffDto;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountStaff\Queries\Auth\UpdateProfileForStaffQuery;
use Illuminate\Support\Facades\Log;

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
