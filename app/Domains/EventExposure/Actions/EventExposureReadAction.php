<?php

namespace App\Domains\EventExposure\Actions;

use App\Domains\EventExposure\Dto\EventExposureDto;
use App\Domains\EventExposure\Models\EventExposure;
use App\Domains\EventExposure\Queries\EventExposureQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

final class EventExposureReadAction
{
    public function __construct(private readonly EventExposureQuery $query) {}

    public function categories(): array
    {
        Gate::authorize('viewAny', EventExposure::class);

        return $this->query->categories()->get(['id', 'name', 'group_code'])
            ->filter(fn ($row) => app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->presentation($row, 'HOSPITAL_EVENT')['enabled'])
            ->sortBy(fn ($row) => app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->presentation($row, 'HOSPITAL_EVENT')['sort_order'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->displayNameFor('HOSPITAL_EVENT'), 'group_code' => $row->group_code])->values()->all();
    }

    public function board(array $filters): array
    {
        Gate::authorize('viewAny', EventExposure::class);

        return DB::transaction(function () use ($filters) {
            $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
            $now = now()->startOfSecond();
            $keys = $this->query->activeKeys($scope, $now);
            $entries = $this->query->entries($scope, $now, $filters);
            $active = (clone $entries)->where('active', 1)->get()->sortBy(fn ($r) => array_search($r->source_key, $keys, true))->values();
            $inactive = (clone $entries)->where('active', 0)->orderByRaw('case when start_at > ? then 0 when end_at < ? then 2 else 1 end', [$now, $now])->orderBy('start_at')->orderBy('source')->orderBy('id')->paginate(15, ['*'], 'page', $filters['page'] ?? 1);

            return ['active' => EventExposureDto::rows($active, $now, $scope['key']), 'inactive' => ['items' => EventExposureDto::rows($inactive->items(), $now, $scope['key']), 'meta' => ['current_page' => $inactive->currentPage(), 'last_page' => $inactive->lastPage(), 'per_page' => 15, 'total' => $inactive->total()]],
                'active_keys' => $keys, 'count' => count($keys), 'limit' => EventExposure::LIMIT, 'revision' => (int) ($this->query->position($scope)?->revision ?? 0)];
        });
    }

    public function targets(array $filters): array
    {
        Gate::authorize('viewAny', EventExposure::class);
        $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
        $query = $this->query->events($scope)->with('hospital:id,name')->where(fn ($q) => $q->where('is_event_period_unlimited', true)->orWhereNull('event_end_at')->orWhere('event_end_at', '>=', now()->startOfDay()));
        if ($term = trim($filters['q'] ?? '')) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', '%'.$term.'%'));
                if (ctype_digit($term)) {
                    $q->orWhere('hospital_events.id', (int) $term);
                }
            });
        }
        $page = $query->orderByDesc('id')->paginate(20, ['*'], 'page', $filters['page'] ?? 1);

        return ['items' => $page->map(fn ($e) => ['id' => $e->id, 'name' => $e->name, 'hospital_name' => $e->hospital?->name, 'start_at' => $e->event_start_at?->toISOString(), 'end_at' => $e->is_event_period_unlimited ? null : $e->event_end_at?->copy()->endOfDay()->toISOString()])->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'per_page' => 20]];
    }

    public function listing(array $filters): array
    {
        return DB::transaction(function () use ($filters) {
            $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
            $now = now()->startOfSecond();
            $keys = $this->query->activeKeys($scope, $now);
            $entries = $this->query->entries($scope, $now)->where('active', 1)->get()->sortBy(fn ($e) => array_search($e->source_key, $keys, true))->values();

            return array_map(fn ($row) => [
                ...array_intersect_key($row, array_flip(['source_key', 'event_id', 'name', 'hospital_name', 'image'])),
                'click_url' => URL::temporarySignedRoute('user.event-exposures.click', $now->copy()->addMinutes(30), [
                    'placement' => $scope['placement'], 'sourceKey' => $row['source_key'],
                    ...($scope['category_id'] === null ? [] : ['category_id' => $scope['category_id']]),
                ], absolute: false),
            ], EventExposureDto::rows($entries, $now, $scope['key']));
        });
    }
}
