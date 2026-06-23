<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalEventDBListForStaffQuery
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
        return HospitalEventDB::query()
            ->select([
                'id',
                'account_user_id',
                'hospital_id',
                'hospital_event_id',
                'hospital_doctor_id',
                'name',
                'phone',
                'phone_normalized',
                'contact_method',
                'preferred_time',
                'event_price',
                'consultation_price',
                'status',
                'allow_status',
                'contacted_at',
                'confirmed_at',
                'duplicated_at',
                'author_ip',
                'user_agent',
                'privacy_agreed_at',
                'marketing_agreed_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'accountUser:id,name,nickname,email,phone,status',
                'hospital:id,name',
                'event:id,hospital_id,name,event_price,consultation_price',
                'doctor:id,hospital_id,name,position',
            ]);
    }

    private function applyFilters(Builder $builder, array $filters): void
    {
        if (! empty($filters['q'])) {
            $this->applySearch($builder, (string) $filters['q']);
        }

        if (is_array($filters['contact_methods'] ?? null) && $filters['contact_methods'] !== []) {
            $builder->whereIn('contact_method', $filters['contact_methods']);
        }

        if (is_array($filters['preferred_times'] ?? null) && $filters['preferred_times'] !== []) {
            $builder->whereIn('preferred_time', $filters['preferred_times']);
        }

        if (is_array($filters['statuses'] ?? null) && $filters['statuses'] !== []) {
            $builder->whereIn('status', $filters['statuses']);
        }

        if (is_array($filters['allow_statuses'] ?? null) && $filters['allow_statuses'] !== []) {
            $builder->whereIn('allow_status', $filters['allow_statuses']);
        }

        if (! empty($filters['account_user_id'])) {
            $builder->where('account_user_id', (int) $filters['account_user_id']);
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['hospital_event_id'])) {
            $builder->where('hospital_event_id', (int) $filters['hospital_event_id']);
        }

        if (! empty($filters['hospital_doctor_id'])) {
            $builder->where('hospital_doctor_id', (int) $filters['hospital_doctor_id']);
        }

        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            DateRangeFilter::apply($builder, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        }

        $this->applyAmountRangeFilter(
            $builder,
            (string) ($filters['amount_metric'] ?? 'all'),
            $filters['amount_min'] ?? null,
            $filters['amount_max'] ?? null,
        );
    }

    private function applySearch(Builder $builder, string $keyword): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }

        $normalizedPhone = HospitalEventDB::normalizePhone($keyword);

        $builder->where(function ($query) use ($keyword, $normalizedPhone): void {
            if (ctype_digit($keyword)) {
                $query->whereKey((int) $keyword);
            }

            $query
                ->orWhere('name', 'like', "%{$keyword}%")
                ->orWhere('phone', 'like', "%{$keyword}%")
                ->when($normalizedPhone !== '', fn ($phoneQuery) => $phoneQuery
                    ->orWhere('phone_normalized', 'like', "%{$normalizedPhone}%"))
                ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery
                    ->where('name', 'like', "%{$keyword}%"))
                ->orWhereHas('event', fn ($eventQuery) => $eventQuery
                    ->where('name', 'like', "%{$keyword}%"));
        });
    }

    private function applyAmountRangeFilter(Builder $builder, string $metric, mixed $min, mixed $max): void
    {
        if ($min === null && $max === null) {
            return;
        }

        $columns = match ($metric) {
            'event_price' => ['event_price'],
            'consultation_price' => ['consultation_price'],
            default => ['event_price', 'consultation_price'],
        };

        $builder->where(function ($query) use ($columns, $min, $max): void {
            foreach ($columns as $column) {
                $query->orWhere(function ($nested) use ($column, $min, $max): void {
                    if ($min !== null) {
                        $nested->where($column, '>=', (int) $min);
                    }

                    if ($max !== null) {
                        $nested->where($column, '<=', (int) $max);
                    }
                });
            }
        });
    }
}
