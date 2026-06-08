<?php

namespace App\Domains\HospitalDoctor\Queries\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Eloquent\Collection;

/**
 * HospitalDoctorOptionListForStaffQuery 역할 정의.
 * 의료진 관리 화면에서 병의원 소속 의료진명 자동완성 목록을 조회한다.
 */
final class HospitalDoctorOptionListForStaffQuery
{
    /**
     * @param array{hospital_id: int, q?: string|null, per_page?: int} $filters
     * @return Collection<int, HospitalDoctor>
     */
    public function get(array $filters): Collection
    {
        $hospitalId = (int) $filters['hospital_id'];
        $q = is_string($filters['q'] ?? null) ? trim($filters['q']) : null;
        $perPage = (int) ($filters['per_page'] ?? 3);

        $builder = HospitalDoctor::query()
            ->select(['id', 'hospital_id', 'name', 'position', 'sort_order'])
            ->where('hospital_id', $hospitalId);

        if ($q !== null && $q !== '') {
            $builder->where('name', 'like', "%{$q}%");
        }

        return $builder
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(max(1, min($perPage, 10)))
            ->get();
    }
}
