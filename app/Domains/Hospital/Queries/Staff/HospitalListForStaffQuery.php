<?php

namespace App\Domains\Hospital\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Common\Category\Models\Category;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * HospitalListForStaffQuery 역할 정의.
 * 병원 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class HospitalListForStaffQuery
{
    /**
     * 뷰랩 전용 병원 리스트
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $q         = is_string($filters['q'] ?? null) ? trim($filters['q']) : null;
        $startDate = $filters['start_date'] ?? null;
        $endDate   = $filters['end_date'] ?? null;
        $updatedStartDate = $filters['updated_start_date'] ?? null;
        $updatedEndDate   = $filters['updated_end_date'] ?? null;
        $status    = $filters['status'] ?? null;
        $accountStatus = $filters['account_status'] ?? null;
        $allow     = $filters['allow_status'] ?? null;
        $departments = $filters['department'] ?? null;
        $categoryIds = $filters['category_ids'] ?? null;
        $include = $filters['include'] ?? [];
        $sort      = $filters['sort'] ?? 'id';
        $direction = $filters['direction'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 15;

        // 필요한 컬러만 정의
        $builder = Hospital::query()->select([
            'id',
            'name',
            'department',
            'email',
            'tel',
            'view_count',
            'evaluation_count',
            'evaluation_average_rating',
            'allow_status',
            'status',
            'created_at',
            'updated_at',
        ]);

        $builder
            ->with([
                'logoMedia',
                'accountHospital:id,hospital_id,nickname,email,phone,status,last_login_at',
            ])
            ->withCount([
                'hospitalEvents as event_count',
                'hospitalReviews as surgery_review_count' => fn (Builder $query) => $query
                    ->where('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
                    ->where('status', HospitalReview::STATUS_ACTIVE),
                'hospitalReviews as treatment_review_count' => fn (Builder $query) => $query
                    ->where('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
                    ->where('status', HospitalReview::STATUS_ACTIVE),
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

        if (is_array($include) && in_array('features', $include, true)) {
            $builder->with([
                'features' => fn ($query) => $query
                    ->select(['hospital_features.id', 'hospital_features.code', 'hospital_features.name', 'hospital_features.sort_order', 'hospital_features.status'])
                    ->orderBy('hospital_features.sort_order')
                    ->orderBy('hospital_features.id'),
            ]);
        }

        // 검색: HID exact match + 병의원명 / 병원아이디 LIKE 검색
        if ($q !== null && $q !== '') {
            $searchId = null;
            if (ctype_digit($q)) {
                $searchId = (int) $q;
            } elseif (preg_match('/^(?:HID|UID)[-_ ]?(\d+)$/i', $q, $matches) === 1) {
                $searchId = (int) $matches[1];
            }

            $builder->where(function (Builder $w) use ($q, $searchId) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhereHas('accountHospital', fn (Builder $accountQuery) => $accountQuery
                        ->where('nickname', 'like', "%{$q}%"));

                if ($searchId !== null) {
                    $w->orWhere('id', $searchId);
                }
            });
        }

        DateRangeFilter::apply($builder, 'created_at', $startDate, $endDate);
        DateRangeFilter::apply($builder, 'updated_at', $updatedStartDate, $updatedEndDate);

        // 필터(status, account_status, allow_status)
        if (is_array($status) && $status !== []) {
            $builder->whereIn('status', $status);
        }

        if (is_array($accountStatus) && $accountStatus !== []) {
            $builder->whereHas('accountHospital', fn (Builder $query) => $query->whereIn('status', $accountStatus));
        }

        if (is_array($allow) && $allow !== []) {
            $builder->whereIn('allow_status', $allow);
        }

        if (is_array($departments) && $departments !== []) {
            $builder->whereIn('department', $departments);
        }

        if (is_array($categoryIds) && $categoryIds !== []) {
            $expandedCategoryIds = $this->expandWithDescendants($categoryIds);

            if ($expandedCategoryIds === []) {
                $builder->whereRaw('1 = 0');
            } else {
                $builder->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $expandedCategoryIds));
            }
        }

        if ($sort === 'last_login_at') {
            $builder->orderBy(
                AccountHospital::query()
                    ->select('last_login_at')
                    ->whereColumn('account_hospitals.hospital_id', 'hospitals.id')
                    ->limit(1),
                $direction
            );
        } else {
            $builder->orderBy($sort, $direction);
        }

        return $builder->paginate($perPage)->withQueryString();
    }

    /**
     * @param array<int, int|string> $categoryIds
     * @return array<int, int>
     */
    private function expandWithDescendants(array $categoryIds): array
    {
        $selectedCategoryIds = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->all();

        if ($selectedCategoryIds === []) {
            return [];
        }

        $selectedCategories = Category::query()
            ->select(['id', 'domain', 'name', 'full_path'])
            ->whereIn('id', $selectedCategoryIds)
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->get();

        if ($selectedCategories->isEmpty()) {
            return [];
        }

        return Category::query()
            ->select('id')
            ->where(function ($query) use ($selectedCategories): void {
                foreach ($selectedCategories as $selectedCategory) {
                    $pathPrefix = trim((string) ($selectedCategory->full_path ?: $selectedCategory->name));

                    $query->orWhere(function ($nested) use ($selectedCategory, $pathPrefix): void {
                        $nested->where('domain', (string) $selectedCategory->domain)
                            ->where(function ($pathQuery) use ($selectedCategory, $pathPrefix): void {
                                $pathQuery->where('id', (int) $selectedCategory->id);

                                if ($pathPrefix !== '') {
                                    $pathQuery->orWhere('full_path', 'like', $pathPrefix . ' > %');
                                }
                            });
                    });
                }
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
