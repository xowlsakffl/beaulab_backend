<?php

namespace App\Domains\HospitalFeature\Queries\Staff;

use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Database\Eloquent\Collection;

/**
 * HospitalFeatureListForStaffQuery 역할 정의.
 * 병원 특징 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalFeatureListForStaffQuery
{
    /**
     * @param array{
     *   q?: string|null,
     *   status?: array<int, string>|null,
     *   sort?: string,
     *   direction?: 'asc'|'desc'
     * } $filters
     * @return Collection<int, HospitalFeature>
     */
    public function get(array $filters): Collection
    {
        $q = is_string($filters['q'] ?? null) ? trim($filters['q']) : null;
        $status = $filters['status'] ?? [HospitalFeature::STATUS_ACTIVE];
        $sort = $filters['sort'] ?? 'sort_order';
        $direction = $filters['direction'] ?? 'asc';

        $builder = HospitalFeature::query()->select([
            'id',
            'code',
            'name',
            'sort_order',
            'status',
            'created_at',
            'updated_at',
        ]);

        if ($q !== null && $q !== '') {
            $builder->where(function ($query) use ($q): void {
                $query
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        return $builder
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->get();
    }
}
