<?php

namespace App\Domains\BannerExposure\Actions;

use App\Common\Support\PaginatedResponse;
use App\Domains\BannerExposure\Dto\BannerExposureDto;
use App\Domains\BannerExposure\Models\BannerExposure;
use App\Domains\BannerExposure\Queries\BannerExposureQuery;
use App\Domains\BannerExposure\Queries\BannerExposureTargetQuery;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class BannerExposureReadAction
{
    public function __construct(private readonly BannerExposureQuery $query) {}

    public function board(array $filters): array
    {
        Gate::authorize('viewAny', BannerExposure::class);

        return DB::transaction(function () use ($filters): array {
            $now = now()->startOfSecond();
            $placement = $filters['placement'];
            $keys = $this->query->activeKeys($placement, $now);
            $entries = $this->query->entries($placement, $now, $filters);
            $active = (clone $entries)->where('active', 1)->get()->sortBy(fn ($row) => array_search($row->source_key, $keys, true))->values();
            $inactive = (clone $entries)->where('active', 0)
                ->orderByRaw('case when start_at > ? then 0 when end_at < ? then 2 else 1 end', [$now, $now])
                ->orderBy('start_at')->orderBy('source')->orderBy('id')->paginate(15, ['*'], 'page', $filters['page'] ?? 1);

            return [
                'active' => BannerExposureDto::rows($active, $now),
                'inactive' => ['items' => BannerExposureDto::rows($inactive->items(), $now),
                    'meta' => ['current_page' => $inactive->currentPage(), 'last_page' => $inactive->lastPage(), 'per_page' => 15, 'total' => $inactive->total()]],
                'active_keys' => $keys, 'count' => count($keys), 'limit' => BannerExposure::LIMIT,
                'can_register' => true,
                'revision' => (int) $this->query->placement($placement)->revision,
            ];
        });
    }

    public function detail(BannerExposure $banner): array
    {
        Gate::authorize('view', $banner);

        return BannerExposureDto::detail($banner->load(BannerExposure::RELATIONS));
    }

    public function targets(array $filters): array
    {
        Gate::authorize('viewAny', BannerExposure::class);

        return app(BannerExposureTargetQuery::class)->options($filters);
    }

    public function histories(BannerExposure $banner, array $filters): array
    {
        Gate::authorize('view', $banner);
        $paginator = $banner->operationHistories()->with(['actor', 'changes'])->paginate(10, ['*'], 'operation_histories_page', $filters['operation_histories_page'] ?? 1);

        return PaginatedResponse::fromPaginator($paginator, fn ($row) => OperationHistoryDto::fromModel($row)->toArray());
    }
}
