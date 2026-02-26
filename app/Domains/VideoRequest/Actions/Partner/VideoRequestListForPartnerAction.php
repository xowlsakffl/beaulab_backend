<?php

namespace App\Domains\VideoRequest\Actions\Partner;

use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestListForPartnerQuery;
use Illuminate\Support\Facades\Gate;

final class VideoRequestListForPartnerAction
{
    public function __construct(private readonly VideoRequestListForPartnerQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', VideoRequest::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($videoRequest) => VideoRequestForPartnerDto::fromModel($videoRequest)->toArray())
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
