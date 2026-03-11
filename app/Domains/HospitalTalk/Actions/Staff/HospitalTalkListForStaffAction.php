<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkForStaffDto;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkListForStaffAction
{
    public function __construct(
        private readonly HospitalTalkListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalTalk::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($talk) => HospitalTalkForStaffDto::fromModel($talk)->toArray())
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
