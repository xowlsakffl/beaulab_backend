<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalNameExistsForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * HospitalCheckNameForStaffAction 역할 정의.
 * 병원명 중복 확인 유스케이스를 담당하며, 권한 확인과 Query 호출 결과를 응답 구조로 정리한다.
 */
final class HospitalCheckNameForStaffAction
{
    public function __construct(
        private readonly HospitalNameExistsForStaffQuery $query,
    ) {}

    public function execute(string $name): array
    {
        Gate::authorize('create', Hospital::class);

        $exists = $this->query->exists($name);

        return [
            'exists' => $exists,
            'available' => ! $exists,
        ];
    }
}
