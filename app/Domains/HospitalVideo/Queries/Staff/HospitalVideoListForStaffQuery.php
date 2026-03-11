<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalVideoListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $include = $filters['include'] ?? [];

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
                'publish_start_at',
                'publish_end_at',
                'is_publish_period_unlimited',
                'created_at',
                'updated_at',
            ]);

        if (is_array($include) && in_array('categories', $include, true)) {
            $builder->with([
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.name', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('external_video_id', 'like', "%{$q}%")
                    ->orWhere('external_video_url', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        $builder->orderBy($filters['sort'] ?? 'id', $filters['direction'] ?? 'desc');

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
