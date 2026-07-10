<?php

namespace App\Domains\HospitalVideo\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalVideoListForStaffQuery
{
    public function __construct(
        private readonly HospitalVideoSummaryForStaffQuery $summaryQuery,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalVideo::query()
            ->select([
                'id',
                'hospital_id',
                'doctor_id',
                'manager_staff_id',
                'title',
                'external_video_url',
                'duration_seconds',
                'hospital_status',
                'admin_status',
                'view_count',
                'like_count',
                'created_at',
                'updated_at',
            ])
            ->with([
                'hospital:id,name',
                'hospital.businessRegistration:id,hospital_id,business_number',
                'doctor:id,name,position',
                'managerStaff:id,name,email',
                'thumbnailMedia',
                'contentReportState',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ]);

        $this->summaryQuery->applySummaryFilter($builder, (string) ($filters['summary_filter'] ?? ''));

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['category_id'])) {
            $builder->whereHas('categories', fn (Builder $query) => $query
                ->where('categories.id', (int) $filters['category_id']));
        }

        if (! empty($filters['q'])) {
            $this->applySearch($builder, (string) $filters['q']);
        }

        if (is_array($filters['hospital_status'] ?? null) && $filters['hospital_status'] !== []) {
            $builder->whereIn('hospital_status', $filters['hospital_status']);
        }

        if (is_array($filters['admin_status'] ?? null) && $filters['admin_status'] !== []) {
            $builder->whereIn('admin_status', $filters['admin_status']);
        }

        if (is_array($filters['report_status'] ?? null) && $filters['report_status'] !== []) {
            $this->applyReportStatusFilter($builder, $filters['report_status']);
        }

        $this->applyNumberRange($builder, 'view_count', $filters['view_count_min'] ?? null, $filters['view_count_max'] ?? null);
        $this->applyNumberRange($builder, 'like_count', $filters['like_count_min'] ?? null, $filters['like_count_max'] ?? null);
        $this->applyReportCountRange($builder, $filters['report_count_min'] ?? null, $filters['report_count_max'] ?? null);

        DateRangeFilter::apply($builder, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);

        $sort = in_array($filters['sort'] ?? null, [
            'id',
            'title',
            'hospital_status',
            'admin_status',
            'view_count',
            'like_count',
            'created_at',
            'updated_at',
        ], true) ? (string) $filters['sort'] : 'id';

        $builder->orderBy($sort, ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc');
        if ($sort !== 'id') {
            $builder->orderByDesc('id');
        }

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
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
                ->orWhere('title', 'like', "%{$q}%")
                ->orWhereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                    ->where('name', 'like', "%{$q}%"));
        });
    }

    /**
     * @param  array<int, string>  $statuses
     */
    private function applyReportStatusFilter(Builder $builder, array $statuses): void
    {
        $statuses = array_values(array_unique(array_filter($statuses)));
        if ($statuses === []) {
            return;
        }

        $includeNone = in_array(ContentReportState::STATUS_NONE, $statuses, true);
        $nonNoneStatuses = array_values(array_filter(
            $statuses,
            static fn (string $status): bool => $status !== ContentReportState::STATUS_NONE,
        ));

        $builder->where(function (Builder $query) use ($includeNone, $nonNoneStatuses): void {
            if ($includeNone) {
                $query
                    ->whereDoesntHave('contentReportState')
                    ->orWhereHas('contentReportState', fn (Builder $stateQuery) => $stateQuery
                        ->where('report_status', ContentReportState::STATUS_NONE));
            }

            if ($nonNoneStatuses !== []) {
                $query->orWhereHas('contentReportState', fn (Builder $stateQuery) => $stateQuery
                    ->whereIn('report_status', $nonNoneStatuses));
            }
        });
    }

    private function applyNumberRange(Builder $builder, string $column, mixed $min, mixed $max): void
    {
        if ($min !== null && $min !== '') {
            $builder->where($column, '>=', (int) $min);
        }

        if ($max !== null && $max !== '') {
            $builder->where($column, '<=', (int) $max);
        }
    }

    private function applyReportCountRange(Builder $builder, mixed $min, mixed $max): void
    {
        if ($min !== null && $min !== '' && (int) $min > 0) {
            $builder->whereHas('contentReportState', fn (Builder $query) => $query
                ->where('report_count', '>=', (int) $min));
        }

        if ($max !== null && $max !== '') {
            $maxValue = (int) $max;

            $builder->where(function (Builder $query) use ($maxValue): void {
                $query
                    ->whereDoesntHave('contentReportState')
                    ->orWhereHas('contentReportState', fn (Builder $stateQuery) => $stateQuery
                        ->where('report_count', '<=', $maxValue));
            });
        }
    }
}
