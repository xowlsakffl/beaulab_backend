<?php

namespace App\Domains\VideoRequest\Actions\Beauty;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\VideoRequest\Dto\Partner\VideoRequestForPartnerDto;
use App\Domains\VideoRequest\Models\VideoRequest;
use App\Domains\VideoRequest\Queries\Partner\VideoRequestListForPartnerQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class VideoRequestListForBeautyAction
{
    public function __construct(private readonly VideoRequestListForPartnerQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', VideoRequest::class);

        /** @var AccountBeauty $partner */
        $partner = Auth::user();

        $paginator = $this->query->paginate($partner, $filters);

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