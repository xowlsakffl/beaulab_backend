<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * HospitalVideoDoctorOptionListForStaffQuery 역할 정의.
 * 병원 동영상 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalVideoDoctorOptionListForStaffQuery
{
    /**
     * @param array{hospital_id: int, q?: string|null, per_page?: int} $filters
     * @return Collection<int, HospitalDoctor>
     */
    public function get(array $filters): Collection
    {
        $hospitalId = (int) $filters['hospital_id'];
        $q = is_string($filters['q'] ?? null) ? trim($filters['q']) : null;
        $perPage = (int) ($filters['per_page'] ?? 20);

        $builder = HospitalDoctor::query()
            ->select(['id', 'hospital_id', 'name', 'position', 'sort_order'])
            ->where('hospital_id', $hospitalId);

        if ($q !== null && $q !== '') {
            $searchId = ctype_digit($q) ? (int) $q : null;

            $builder->where(function (Builder $query) use ($q, $searchId): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");

                if ($searchId !== null) {
                    $query->orWhere('id', $searchId);
                }
            });
        }

        return $builder
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->limit(max(1, min($perPage, 50)))
            ->get();
    }
}
