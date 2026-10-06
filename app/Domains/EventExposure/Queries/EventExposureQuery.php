<?php

namespace App\Domains\EventExposure\Queries;

use App\Domains\Common\Category\Models\Category;
use App\Domains\EventExposure\Models\EventExposure;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EventExposureQuery
{
    public function categories()
    {
        return Category::query()->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->whereNull('parent_id')
            ->whereIn('group_code', ['SURGERY', 'TREATMENT'])->where('status', 'ACTIVE')->orderBy('sort_order')->orderBy('id');
    }

    public function scope(string $placement, ?int $categoryId = null): array
    {
        if (! in_array($placement, EventExposure::PLACEMENTS, true)) {
            $this->invalid('placement', '노출 위치를 선택해 주세요.');
        }
        $group = str_starts_with($placement, 'SURGERY_') ? 'SURGERY' : 'TREATMENT';
        if (str_ends_with($placement, 'CATEGORY_BANNER')) {
            if ($categoryId !== null && ! $this->categories()->whereKey($categoryId)->where('group_code', $group)->exists()) {
                $this->invalid('category_id', '해당 영역의 활성 대분류를 선택해 주세요.');
            }
        } elseif ($categoryId) {
            $this->invalid('category_id', '카테고리별 상단배너에서만 대분류를 지정할 수 있습니다.');
        }
        $categories = Category::query()->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->where('group_code', $group)->where('status', 'ACTIVE')->get(['id', 'parent_id']);
        $ids = $categoryId ? [$categoryId] : $this->categories()->where('group_code', $group)->pluck('id')->all();
        do {
            $before = count($ids);
            foreach ($categories as $category) {
                if (in_array($category->parent_id, $ids, true) && ! in_array($category->id, $ids, true)) {
                    $ids[] = $category->id;
                }
            }
        } while (count($ids) !== $before);

        return ['placement' => $placement, 'category_id' => $categoryId, 'key' => $placement.($categoryId ? ':'.$categoryId : ''), 'category_ids' => $ids];
    }

    public function events(array $scope, ?CarbonInterface $at = null)
    {
        $query = HospitalEvent::query()->where('allow_status', 'APPROVED')->where('hospital_status', 'PUBLIC')->where('admin_status', 'NORMAL')
            ->whereHas('hospital', fn ($q) => $q->where('allow_status', 'APPROVED')->where('status', 'ACTIVE'))
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $scope['category_ids']));

        return $at ? $query->applicationOpen($at) : $query;
    }

    public function ads(array $scope)
    {
        $query = HospitalEventAd::query()->where('placement', $scope['placement'])->where('allow_status', 'APPROVED');
        if (str_ends_with($scope['placement'], 'CATEGORY_BANNER')) {
            // 전체는 독립 노출 위치이며 개별 카테고리 광고를 합산하지 않는다.
            return $scope['category_id'] === null
                ? $query->whereDoesntHave('categories')
                : $query->whereHas('categories', fn ($c) => $c->where('categories.id', $scope['category_id']));
        }

        return $query;
    }

    public function entries(array $scope, CarbonInterface $at, array $filters = [])
    {
        $at = $at->copy()->startOfSecond();
        $eligible = $this->events($scope, $at)->select('hospital_events.id');
        $manual = EventExposure::query()->where('position_key', $scope['key'])
            ->selectRaw("id, 'event' as source, concat('event:', id) as source_key, start_at, end_at")
            ->selectRaw("case when status = 'ACTIVE' and start_at <= ? and end_at >= ? and hospital_event_id in (".$eligible->toSql().') then 1 else 0 end as active', [$at, $at, ...$eligible->getBindings()]);
        $ads = $this->ads($scope)->selectRaw("id, 'ad' as source, concat('ad:', id) as source_key, start_at, end_at")
            ->selectRaw('case when start_at <= ? and end_at >= ? then 1 else 0 end as active', [$at, $at]);
        if ($term = trim($filters['q'] ?? '')) {
            foreach ([[$manual, 'event', 'creator'], [$ads, 'hospitalEvent', 'managerStaff']] as [$builder, $eventRelation, $staffRelation]) {
                $builder->where(function ($q) use ($term, $eventRelation, $staffRelation) {
                    $q->whereHas($eventRelation, function ($event) use ($term) {
                        $event->where('name', 'like', '%'.$term.'%')->orWhereHas('hospital', fn ($hospital) => $hospital->where('name', 'like', '%'.$term.'%'));
                        if (ctype_digit($term)) {
                            $event->orWhere('hospital_events.id', (int) $term);
                        }
                    })->orWhereHas($staffRelation, fn ($staff) => $staff->where('name', 'like', '%'.$term.'%'));
                });
            }
        }

        return DB::query()->fromSub($manual->toBase()->unionAll($ads->toBase()), 'entries')
            ->when($filters['start_date'] ?? null, fn ($q, $date) => $q->where('end_at', '>=', $date.' 00:00:00'))
            ->when($filters['end_date'] ?? null, fn ($q, $date) => $q->where('start_at', '<=', $date.' 23:59:59'));
    }

    public function position(array $scope): ?object
    {
        return DB::table('event_exposure_positions')->where('key', $scope['key'])->first();
    }

    public function activeKeys(array $scope, CarbonInterface $at): array
    {
        $order = json_decode($this->position($scope)?->ordered_keys ?? '[]', true);
        $keys = $this->entries($scope, $at)->where('active', 1)->orderBy('start_at')->orderBy('source')->orderBy('id')->pluck('source_key')->all();

        return [...array_values(array_intersect($order, $keys)), ...array_values(array_diff($keys, $order))];
    }

    private function invalid(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
