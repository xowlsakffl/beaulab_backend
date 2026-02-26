<?php

namespace App\Domains\VideoRequest\Actions\Staff;

use App\Domains\VideoRequest\Dto\Staff\VideoRequestForStaffDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Staff\VideoRequestListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class VideoRequestListForStaffAction
{
    public function __construct(private readonly VideoRequestListForStaffQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', VideoRequest::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($videoRequest) => VideoRequestForStaffDto::fromModel($videoRequest)->toArray())
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
