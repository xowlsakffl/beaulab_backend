<?php

namespace App\Domains\HospitalEvaluation\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class HospitalEvaluationListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $builder = HospitalEvaluation::query()
            ->select([
                'id',
                'author_id',
                'hospital_id',
                'doctor_id',
                'phone',
                'cost',
                'rating_staff_kindness',
                'rating_surgery_satisfaction',
                'rating_facility',
                'rating_aftercare',
                'rating_cost',
                'average_rating',
                'status',
                'post_status',
                'view_count',
                'receipt_status',
                'receipt_rejection_reason',
                'receipt_rejection_reason_text',
                'created_at',
                'updated_at',
            ])
            ->with([
                'author:id,name,nickname,email',
                'hospital:id,name',
                'hospital.businessRegistration:id,hospital_id,business_number',
                'doctor:id,name,position',
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.code', 'categories.domain', 'categories.name', 'categories.full_path', 'categories.depth', 'categories.sort_order'])
                    ->orderBy('depth')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'contentReportState:id,target_type,target_id,report_status',
            ]);

        if (! empty($filters['q'])) {
            $q = (string) $filters['q'];
            $builder->where(function ($query) use ($q): void {
                $query->where('content', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('author', fn ($authorQuery) => $authorQuery
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('nickname', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%"))
                    ->orWhereHas('hospital', fn ($hospitalQuery) => $hospitalQuery
                        ->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery
                        ->where('name', 'like', "%{$q}%"));

                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            });
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $builder->whereIn('status', $filters['status']);
        }

        if (is_array($filters['report_status'] ?? null) && $filters['report_status'] !== []) {
            $builder->whereHas(
                'contentReportState',
                fn ($query) => $query->whereIn('report_status', $filters['report_status'])
            );
        }

        if (is_array($filters['post_status'] ?? null) && $filters['post_status'] !== []) {
            $builder->whereIn('post_status', $filters['post_status']);
        }

        if (is_array($filters['receipt_status'] ?? null) && $filters['receipt_status'] !== []) {
            $builder->whereIn('receipt_status', $filters['receipt_status']);
        }

        if (! empty($filters['author_id'])) {
            $builder->where('author_id', (int) $filters['author_id']);
        }

        if (! empty($filters['hospital_id'])) {
            $builder->where('hospital_id', (int) $filters['hospital_id']);
        }

        if (! empty($filters['doctor_id'])) {
            $builder->where('doctor_id', (int) $filters['doctor_id']);
        }

        $categoryIds = $filters['category_ids'] ?? null;
        if (is_array($categoryIds) && $categoryIds !== []) {
            $normalizedCategoryIds = collect($categoryIds)
                ->map(static fn (int|string $value): int => (int) $value)
                ->filter(static fn (int $value): bool => $value > 0)
                ->unique()
                ->values()
                ->all();

            if ($normalizedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', function ($query) use ($normalizedCategoryIds): void {
                    $query
                        ->where('categories.domain', Category::DOMAIN_HOSPITAL_EVALUATION)
                        ->whereIn('categories.id', $normalizedCategoryIds);
                });
            }
        }

        if ($filters['cost_min'] !== null) {
            $builder->where('cost', '>=', (int) $filters['cost_min']);
        }

        if ($filters['cost_max'] !== null) {
            $builder->where('cost', '<=', (int) $filters['cost_max']);
        }

        $ratings = $filters['ratings'] ?? null;
        if (is_array($ratings) && $ratings !== []) {
            $normalizedRatings = collect($ratings)
                ->map(static fn (int|string $value): int => (int) $value)
                ->filter(static fn (int $value): bool => $value >= 1 && $value <= 5)
                ->unique()
                ->values()
                ->all();

            if ($normalizedRatings === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->where(function ($query) use ($normalizedRatings): void {
                    foreach ($normalizedRatings as $rating) {
                        $query->orWhereRaw('FLOOR(average_rating) = ?', [$rating]);
                    }
                });
            }
        }

        if ($filters['view_count_min'] !== null) {
            $builder->where('view_count', '>=', (int) $filters['view_count_min']);
        }

        if ($filters['view_count_max'] !== null) {
            $builder->where('view_count', '<=', (int) $filters['view_count_max']);
        }

        DateRangeFilter::apply($builder, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);

        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $sort = (string) ($filters['sort'] ?? 'id');

        if ($sort === 'average_rating') {
            $builder->orderBy('average_rating', $direction);
        } else {
            $builder->orderBy($sort, $direction);
        }

        return $builder->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();
    }
}
