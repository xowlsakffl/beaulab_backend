<?php

namespace App\Domains\VideoExposure\Actions;

use App\Domains\VideoExposure\Dto\VideoExposureDto;
use App\Domains\VideoExposure\Models\VideoExposure;
use App\Domains\VideoExposure\Queries\VideoExposureQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

final class VideoExposureReadAction
{
    public function __construct(private readonly VideoExposureQuery $query) {}

    public function categories(): array
    {
        Gate::authorize('viewAny', VideoExposure::class);

        return $this->query->categories()->get(['id', 'name', 'group_code'])
            ->filter(fn ($row) => app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->presentation($row, 'HOSPITAL_VIDEO_CATEGORY')['enabled'])
            ->sortBy(fn ($row) => app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->presentation($row, 'HOSPITAL_VIDEO_CATEGORY')['sort_order'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->displayNameFor('HOSPITAL_VIDEO_CATEGORY'), 'group_code' => $row->group_code])->values()->all();
    }

    public function board(array $filters): array
    {
        Gate::authorize('viewAny', VideoExposure::class);

        return DB::transaction(function () use ($filters) {
            $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
            $now = now()->startOfSecond();
            $keys = $this->query->activeKeys($scope, $now);
            $entries = $this->query->entries($scope, $now, $filters);
            $active = (clone $entries)->where('active', 1)->get()->sortBy(fn ($r) => array_search($r->source_key, $keys, true))->values();
            $inactive = (clone $entries)->where('active', 0)->orderByRaw('case when start_at > ? then 0 when end_at < ? then 2 else 1 end', [$now, $now])->orderBy('start_at')->orderBy('source')->orderBy('id')->paginate(15, ['*'], 'page', $filters['page'] ?? 1);

            return ['active' => VideoExposureDto::rows($active, $now, $scope['key']), 'inactive' => ['items' => VideoExposureDto::rows($inactive->items(), $now, $scope['key']), 'meta' => ['current_page' => $inactive->currentPage(), 'last_page' => $inactive->lastPage(), 'per_page' => 15, 'total' => $inactive->total()]],
                'active_keys' => $keys, 'count' => count($keys), 'limit' => VideoExposure::LIMIT, 'revision' => (int) ($this->query->position($scope)?->revision ?? 0)];
        });
    }

    public function targets(array $filters): array
    {
        Gate::authorize('viewAny', VideoExposure::class);
        $scope = $this->query->scope($filters['placement'], $filters['category_id'] ?? null);
        $query = $this->query->videos($scope)->with(['hospital:id,name', 'thumbnailMedia']);
        if ($term = trim($filters['q'] ?? '')) {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%'.$term.'%')->orWhereHas('hospital', fn ($h) => $h->where('name', 'like', '%'.$term.'%'));
                if (ctype_digit($term)) {
                    $q->orWhere('hospital_videos.id', (int) $term);
                }
            });
        }
        if (isset($term) && ctype_digit($term)) {
            $query->orderByRaw('hospital_videos.id = ? desc', [(int) $term]);
        }
        $page = $query->orderByDesc('id')->paginate(20, ['*'], 'page', $filters['page'] ?? 1);

        return ['items' => $page->map(function ($e) {
            $image = $e->thumbnailMedia;

            return ['id' => $e->id, 'name' => $e->title, 'hospital_name' => $e->hospital?->name,
                'image' => $image ? ['id' => $image->id, 'path' => $image->publicPath(), 'metadata' => $image->publicMetadata()] : null];
        })->all(),
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
                ...array_intersect_key($row, array_flip(['source_key', 'video_id', 'name', 'hospital_name', 'image'])),
                'click_url' => URL::temporarySignedRoute('user.video-exposures.click', $now->copy()->addMinutes(30), [
                    'placement' => $scope['placement'], 'sourceKey' => $row['source_key'],
                    ...($scope['category_id'] === null ? [] : ['category_id' => $scope['category_id']]),
                ], absolute: false),
            ], VideoExposureDto::rows($entries, $now, $scope['key']));
        });
    }
}
