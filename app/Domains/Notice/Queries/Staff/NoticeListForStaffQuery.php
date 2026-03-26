<?php

namespace App\Domains\Notice\Queries\Staff;

use App\Domains\Notice\Models\Notice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class NoticeListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Notice::query()
            ->select([
                'id',
                'channel',
                'title',
                'status',
                'is_pinned',
                'is_publish_period_unlimited',
                'publish_start_at',
                'publish_end_at',
                'is_important',
                'view_count',
                'created_by_staff_id',
                'updated_by_staff_id',
                'created_at',
                'updated_at',
            ])
            ->with([
                'creator:id,name',
            ])
            ->withCount([
                'attachments',
            ]);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $query->where(function (Builder $builder) use ($q): void {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%");
            });
        }

        if (is_array($filters['channel'] ?? null) && $filters['channel'] !== []) {
            $query->whereIn('channel', $filters['channel']);
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $query->whereIn('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['updated_start_date'])) {
            $query->whereDate('updated_at', '>=', $filters['updated_start_date']);
        }

        if (! empty($filters['updated_end_date'])) {
            $query->whereDate('updated_at', '<=', $filters['updated_end_date']);
        }

        if (array_key_exists('is_pinned', $filters) && $filters['is_pinned'] !== null) {
            $query->where('is_pinned', (bool) $filters['is_pinned']);
        }

        if (array_key_exists('is_important', $filters) && $filters['is_important'] !== null) {
            $query->where('is_important', (bool) $filters['is_important']);
        }

        $sort = $filters['sort'] ?? null;
        $direction = $filters['direction'] ?? 'desc';

        if ($sort !== null) {
            $query->orderBy($sort, $direction);
            if ($sort !== 'id') {
                $query->orderByDesc('id');
            }
        } else {
            $query->orderByDesc('is_pinned')
                ->orderByDesc('publish_start_at')
                ->orderByDesc('id');
        }

        return $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }
}
