<?php

namespace App\Domains\HospitalVideoRequest\Queries\Staff;

use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalVideoRequestListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalVideoRequest::query()->select([
            'id', 'hospital_id', 'doctor_id',
            'title', 'review_status', 'is_usage_consented', 'duration_seconds',
            'requested_publish_start_at', 'requested_publish_end_at', 'is_publish_period_unlimited',
            'created_at', 'updated_at',
        ]);

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['review_status'] ?? null) && $filters['review_status'] !== []) {
            $builder->whereIn('review_status', $filters['review_status']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
