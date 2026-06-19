<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalEventRealModelDBListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = $this->baseBuilder();

        $this->applyFilters($builder, $filters);

        $sort = (string) ($filters['sort'] ?? 'id');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $builder->orderBy($sort, $direction);
        if ($sort !== 'id') {
            $builder->orderByDesc('id');
        }

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }

    private function baseBuilder(): Builder
    {
        return HospitalEventRealModelDB::query()
            ->select([
                'id',
                'account_user_id',
                'hospital_id',
                'hospital_event_id',
                'name',
                'gender',
                'birth_date',
                'phone',
                'phone_normalized',
                'height_cm',
                'weight_kg',
                'surgery_period',
                'support_part',
                'instagram_url',
                'blog_url',
                'special_notes',
                'application_reason',
                'inquiry',
                'status',
                'author_ip',
                'user_agent',
                'created_at',
                'updated_at',
            ])
            ->with([
                'accountUser:id,name,nickname,email,phone,status',
                'hospital:id,name',
                'event:id,hospital_id,name',
            ]);
    }

    private function applyFilters(Builder $builder, array $filters): void
    {
        if (! empty($filters['q'])) {
            $this->applySearch($builder, (string) $filters['q']);
        }

        if (is_array($filters['genders'] ?? null) && $filters['genders'] !== []) {
            $builder->whereIn('gender', $filters['genders']);
        }

        if (is_array($filters['statuses'] ?? null) && $filters['statuses'] !== []) {
            $builder->whereIn('status', $filters['statuses']);
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['hospital_event_id'])) {
            $builder->where('hospital_event_id', (int) $filters['hospital_event_id']);
        }

        if (! empty($filters['birth_year_min'])) {
            $builder->where('birth_date', '>=', sprintf('%04d-01-01', (int) $filters['birth_year_min']));
        }

        if (! empty($filters['birth_year_max'])) {
            $builder->where('birth_date', '<=', sprintf('%04d-12-31', (int) $filters['birth_year_max']));
        }

        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            DateRangeFilter::apply($builder, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        }
    }

    private function applySearch(Builder $builder, string $keyword): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }

        $builder->where(function ($query) use ($keyword): void {
            if (ctype_digit($keyword)) {
                $query->whereKey((int) $keyword);
            }

            $query
                ->orWhere('name', 'like', "%{$keyword}%")
                ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery
                    ->where('name', 'like', "%{$keyword}%"))
                ->orWhereHas('event', fn ($eventQuery) => $eventQuery
                    ->where('name', 'like', "%{$keyword}%"));
        });
    }
}
