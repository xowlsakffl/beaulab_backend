<?php

namespace App\Domains\HospitalTalk\Actions\Staff;

use App\Domains\HospitalTalk\Dto\Staff\HospitalTalkCommentForStaffDto;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Domains\HospitalTalk\Queries\Staff\HospitalTalkCommentListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HospitalTalkCommentListForStaffAction
{
    public function __construct(
        private readonly HospitalTalkCommentListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalTalkComment::class);

        $paginator = $this->query->paginate($filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($comment) => HospitalTalkCommentForStaffDto::fromModel($comment)->toArray())
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
