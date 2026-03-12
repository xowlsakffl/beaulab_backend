<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Notice\Dto\Staff\NoticeForStaffDto;
use App\Domains\Notice\Models\Notice;
use App\Domains\Notice\Queries\Staff\NoticeListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class NoticeListForStaffAction
{
    public function __construct(
        private readonly NoticeListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Notice::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(static fn (Notice $notice): array => NoticeForStaffDto::fromModel($notice)->toArray())
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
