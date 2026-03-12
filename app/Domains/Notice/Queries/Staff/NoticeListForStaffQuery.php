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
                'is_visible',
                'is_pinned',
                'pinned_order',
                'is_publish_period_unlimited',
                'publish_start_at',
                'publish_end_at',
                'is_push_enabled',
                'push_sent_at',
                'is_important',
                'view_count',
                'created_by_staff_id',
                'updated_by_staff_id',
                'created_at',
                'updated_at',
            ])
            ->withCount([
                'attachments',
                'popupImage',
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

        if (array_key_exists('is_visible', $filters) && $filters['is_visible'] !== null) {
            $query->where('is_visible', (bool) $filters['is_visible']);
        }

        if (array_key_exists('is_pinned', $filters) && $filters['is_pinned'] !== null) {
            $query->where('is_pinned', (bool) $filters['is_pinned']);
        }

        if (array_key_exists('is_important', $filters) && $filters['is_important'] !== null) {
            $query->where('is_important', (bool) $filters['is_important']);
        }

        if (is_array($filters['exposure_status'] ?? null) && $filters['exposure_status'] !== []) {
            $this->applyExposureStatusFilter($query, $filters['exposure_status']);
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
                ->orderBy('pinned_order')
                ->orderByDesc('publish_start_at')
                ->orderByDesc('id');
        }

        return $query
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    /**
     * @param array<int, string> $statuses
     */
    private function applyExposureStatusFilter(Builder $query, array $statuses): void
    {
        $now = now();

        $query->where(function (Builder $builder) use ($statuses, $now): void {
            foreach ($statuses as $status) {
                $builder->orWhere(function (Builder $inner) use ($status, $now): void {
                    match ($status) {
                        Notice::EXPOSURE_HIDDEN => $inner->where('is_visible', false),
                        Notice::EXPOSURE_SCHEDULED => $inner
                            ->where('is_visible', true)
                            ->whereNotNull('publish_start_at')
                            ->where('publish_start_at', '>', $now),
                        Notice::EXPOSURE_EXPIRED => $inner
                            ->where('is_visible', true)
                            ->where('is_publish_period_unlimited', false)
                            ->whereNotNull('publish_end_at')
                            ->where('publish_end_at', '<', $now),
                        default => $inner
                            ->where('is_visible', true)
                            ->where(function (Builder $scope) use ($now): void {
                                $scope->whereNull('publish_start_at')
                                    ->orWhere('publish_start_at', '<=', $now);
                            })
                            ->where(function (Builder $scope) use ($now): void {
                                $scope->where('is_publish_period_unlimited', true)
                                    ->orWhereNull('publish_end_at')
                                    ->orWhere('publish_end_at', '>=', $now);
                            }),
                    };
                });
            }
        });
    }
}
