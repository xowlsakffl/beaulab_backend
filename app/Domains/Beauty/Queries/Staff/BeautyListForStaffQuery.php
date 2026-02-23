<?php

namespace App\Domains\Beauty\Queries\Staff;

use App\Domains\Beauty\Models\Beauty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BeautyListForStaffQuery
{
    /**
     * 뷰랩 전용 병원 리스트
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q         = $filters['q'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;
        $status    = $filters['status'] ?? null;
        $allow     = $filters['allow_status'] ?? null;
        $sort      = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 15;

        // 필요한 컬러만 정의
        $builder = Beauty::query()->select([
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

        // 등록일(created_at) 기간 필터
        if ($startDate && $endDate) {
            $builder->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        } elseif ($startDate) {
            $builder->whereDate('created_at', '>=', $startDate);
        } elseif ($endDate) {
            $builder->whereDate('created_at', '<=', $endDate);
        }

        // 필터(status, allow_status)
        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        if (is_array($allow) && $allow !== []) {
            $builder->whereIn('allow_status', $allow);
        }

        // 정렬
        $builder->orderBy($sort, $direction);

        return $builder->paginate($perPage)->withQueryString();
    }
}
