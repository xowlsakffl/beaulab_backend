<?php

namespace App\Domains\HospitalEventAd\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class HospitalEventAdListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalEventAd::query()
            ->select([
                'id',
                'hospital_id',
                'hospital_event_id',
                'manager_staff_id',
                'placement',
                'cost',
                'start_at',
                'end_at',
                'allow_status',
                'created_at',
                'updated_at',
            ])
            ->with([
                'hospital:id,name',
                'hospitalEvent:id,hospital_id,name,description,allow_status,hospital_status,admin_status,event_start_at,event_end_at',
                'hospitalEvent.thumbnailImage',
                'categories:id,code,name,full_path,depth',
                'managerStaff:id,name,email',
            ]);

        $this->applyFilters($builder, $filters);

        $sort = in_array($filters['sort'] ?? null, [
            'id',
            'placement',
            'cost',
            'start_at',
            'end_at',
            'allow_status',
            'created_at',
            'updated_at',
        ], true) ? (string) $filters['sort'] : 'id';

        $builder->orderBy($sort, ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc');
        if ($sort !== 'id') {
            $builder->orderByDesc('id');
        }

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    private function applyFilters(Builder $builder, array $filters): void
    {
        if (! empty($filters['q'])) {
            $this->applySearch($builder, (string) $filters['q']);
        }

        if (is_array($filters['placement'] ?? null) && $filters['placement'] !== []) {
            $builder->whereIn('placement', $filters['placement']);
        }

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        if (is_array($filters['ad_status'] ?? null) && $filters['ad_status'] !== []) {
            $this->applyAdStatusFilter($builder, $filters['ad_status']);
        }

        $dateTypes = is_array($filters['date_types'] ?? null) && $filters['date_types'] !== []
            ? $filters['date_types']
            : ['created_at'];

        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $builder->where(function (Builder $query) use ($dateTypes, $filters): void {
                foreach ($dateTypes as $dateType) {
                    $query->orWhere(function (Builder $dateQuery) use ($dateType, $filters): void {
                        if ($dateType === 'ad_period') {
                            $this->applyAdPeriodFilter(
                                $dateQuery,
                                $filters['start_date'] ?? null,
                                $filters['end_date'] ?? null,
                            );

                            return;
                        }

                        DateRangeFilter::apply(
                            $dateQuery,
                            'created_at',
                            $filters['start_date'] ?? null,
                            $filters['end_date'] ?? null,
                        );
                    });
                }
            });
        }
    }

    private function applySearch(Builder $builder, string $q): void
    {
        $q = trim($q);

        if ($q === '') {
            return;
        }

        $builder->where(function (Builder $query) use ($q): void {
            if (ctype_digit($q)) {
                $query->orWhereKey((int) $q);
            }

            $query
                ->orWhereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                    ->where('name', 'like', "%{$q}%"))
                ->orWhereHas('hospitalEvent', fn (Builder $eventQuery) => $eventQuery
                    ->where('name', 'like', "%{$q}%"))
                ->orWhereHas('managerStaff', fn (Builder $staffQuery) => $staffQuery
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%"));
        });
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function applyAdStatusFilter(Builder $builder, array $statuses): void
    {
        $statuses = array_values(array_unique(array_filter($statuses)));
        if ($statuses === []) {
            return;
        }

        $now = now();

        $builder->where(function (Builder $query) use ($statuses, $now): void {
            foreach ($statuses as $status) {
                $query->orWhere(function (Builder $statusQuery) use ($status, $now): void {
                    $statusQuery->where('allow_status', HospitalEventAd::ALLOW_APPROVED);

                    if ($status === HospitalEventAd::AD_STATUS_SCHEDULED) {
                        $statusQuery->where('start_at', '>', $now);

                        return;
                    }

                    if ($status === HospitalEventAd::AD_STATUS_RUNNING) {
                        $statusQuery
                            ->where('start_at', '<=', $now)
                            ->where('end_at', '>=', $now);

                        return;
                    }

                    if ($status === HospitalEventAd::AD_STATUS_ENDED) {
                        $statusQuery->where('end_at', '<', $now);
                    }
                });
            }
        });
    }

    private function applyAdPeriodFilter(Builder $builder, ?string $startDate, ?string $endDate): void
    {
        if ($startDate !== null && $startDate !== '') {
            $builder->where('end_at', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate !== null && $endDate !== '') {
            $builder->where('start_at', '<=', Carbon::parse($endDate)->endOfDay());
        }
    }
}
