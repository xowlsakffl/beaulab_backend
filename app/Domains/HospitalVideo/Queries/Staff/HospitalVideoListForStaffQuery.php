<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * HospitalVideoListForStaffQuery 역할 정의.
 * 병원 동영상 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalVideoListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalVideo::query()
            ->select([
                'id',
                'hospital_id',
                'doctor_id',
                'title',
                'distribution_channel',
                'external_video_id',
                'external_video_url',
                'duration_seconds',
                'status',
                'allow_status',
                'view_count',
                'like_count',
                'allowed_at',
                'publish_start_at',
                'publish_end_at',
                'is_publish_period_unlimited',
                'created_at',
                'updated_at',
            ])
            ->with([
                'hospital:id,name',
                'doctor:id,name',
                'thumbnailMedia',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('external_video_id', 'like', "%{$q}%")
                    ->orWhere('external_video_url', 'like', "%{$q}%")
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        if (is_array($filters['distribution_channel'] ?? null) && $filters['distribution_channel'] !== []) {
            $builder->whereIn('distribution_channel', $filters['distribution_channel']);
        }

        if (! empty($filters['start_date'])) {
            $builder->whereDate('created_at', '>=', (string) $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $builder->whereDate('created_at', '<=', (string) $filters['end_date']);
        }

        if (! empty($filters['allowed_start_date'])) {
            $builder->whereDate('allowed_at', '>=', (string) $filters['allowed_start_date']);
        }

        if (! empty($filters['allowed_end_date'])) {
            $builder->whereDate('allowed_at', '<=', (string) $filters['allowed_end_date']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
