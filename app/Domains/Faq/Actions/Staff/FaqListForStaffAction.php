<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Faq\Dto\Staff\FaqForStaffDto;
use App\Domains\Faq\Models\Faq;
use App\Domains\Faq\Queries\Staff\FaqListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class FaqListForStaffAction
{
    public function __construct(
        private readonly FaqListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Faq::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(static fn (Faq $faq): array => FaqForStaffDto::fromModel($faq)->toArray())
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
