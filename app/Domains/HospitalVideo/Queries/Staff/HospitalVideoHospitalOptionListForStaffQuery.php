<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * HospitalVideoHospitalOptionListForStaffQuery 역할 정의.
 * 병원 동영상 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalVideoHospitalOptionListForStaffQuery
{
    /**
     * @param array{q?: string|null, per_page?: int} $filters
     * @return Collection<int, Hospital>
     */
    public function get(array $filters): Collection
    {
        $q = is_string($filters['q'] ?? null) ? trim($filters['q']) : null;
        $perPage = (int) ($filters['per_page'] ?? 10);

        $builder = Hospital::query()
            ->select(['id', 'name'])
            ->with(['businessRegistration:id,hospital_id,business_number']);

        if ($q !== null && $q !== '') {
            $searchId = ctype_digit($q) ? (int) $q : null;

            $builder->where(function (Builder $query) use ($q, $searchId): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhereHas('businessRegistration', static fn (Builder $businessQuery) => $businessQuery
                        ->where('business_number', 'like', "%{$q}%"));

                if ($searchId !== null) {
                    $query->orWhere('id', $searchId);
                }
            });
        }

        return $builder
            ->orderBy('name')
            ->orderBy('id')
            ->limit(max(1, min($perPage, 20)))
            ->get();
    }
}
