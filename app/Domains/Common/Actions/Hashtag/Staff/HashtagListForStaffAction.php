<?php

namespace App\Domains\Common\Actions\Hashtag\Staff;

use App\Domains\Common\Models\Hashtag\Hashtag;
use App\Domains\Common\Queries\Hashtag\Staff\HashtagListForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class HashtagListForStaffAction
{
    public function __construct(
        private readonly HashtagListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', Hashtag::class);

        $paginator = $this->query->paginate($filters);

        $items = collect($paginator->items())
            ->map(fn (Hashtag $hashtag) => $this->toArray($hashtag))
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    private function toArray(Hashtag $hashtag): array
    {
        $assignmentCount = (int) ($hashtag->getAttribute('assignment_count') ?? 0);

        return [
            'id' => (int) $hashtag->id,
            'name' => (string) $hashtag->name,
            'normalized_name' => (string) $hashtag->normalized_name,
            'status' => $hashtag->resolveStatus(),
            'usage_count' => $hashtag->resolveUsageCount($assignmentCount),
            'assignment_count' => $assignmentCount,
            'created_at' => optional($hashtag->created_at)?->toISOString(),
            'updated_at' => optional($hashtag->updated_at)?->toISOString(),
        ];
    }
}
