<?php

declare(strict_types=1);

namespace App\Domains\HospitalEntry\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalEntryListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalEntry::query()
            ->select([
                'id',
                'hospital_name',
                'address',
                'address_detail',
                'ceo_name',
                'applicant_name',
                'allow_status',
                'created_at',
            ]);

        $this->applyFilters($builder, $filters);

        $sort = (string) ($filters['sort'] ?? 'id');
        $direction = (string) ($filters['direction'] ?? 'desc');

        $builder->orderBy($sort, $direction);
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

        if (is_array($filters['allow_status'] ?? null) && $filters['allow_status'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_status']);
        }

        DateRangeFilter::apply($builder, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
    }

    private function applySearch(Builder $builder, string $keyword): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }

        $builder->where(function (Builder $query) use ($keyword): void {
            if (ctype_digit($keyword)) {
                $query->whereKey((int) $keyword);
            }

            $query
                ->orWhere('hospital_name', 'like', "%{$keyword}%")
                ->orWhere('ceo_name', 'like', "%{$keyword}%")
                ->orWhere('applicant_name', 'like', "%{$keyword}%");
        });
    }
}
