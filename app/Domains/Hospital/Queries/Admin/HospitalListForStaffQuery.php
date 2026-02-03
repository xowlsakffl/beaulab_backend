<?php

namespace App\Domains\Hospital\Queries\Admin;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalListForStaffQuery
{
    /**
     * 뷰랩 전용 병원 리스트
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q         = $filters['q'] ?? null;
        $status    = $filters['status'] ?? null;
        $allow     = $filters['allow_status'] ?? null;
        $sort      = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 15;

        // 필요한 컬러만 정의
        $builder = Hospital::query()->select([
            'id',
            'name',
            'address',
            'tel',
            'view_count',
            'allow_status',
            'status',
            'created_at',
            'updated_at',
        ]);

        // 검색: name / address / tel LIKE 검색
        if ($q) {
            $builder->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('tel', 'like', "%{$q}%");
            });
        }

        // 필터(status, allow_status)
        if ($status) {
            $builder->where('status', $status);
        }

        if ($allow) {
            $builder->where('allow_status', $allow);
        }

        // 정렬
        $builder->orderBy($sort, $direction);

        return $builder->paginate($perPage)->withQueryString();
    }
}
