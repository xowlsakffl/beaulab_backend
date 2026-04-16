<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkVisibilityBulkUpdateForStaffQuery;
use Illuminate\Support\Facades\Gate;

/**
 * 토크 다중 노출 상태 변경 유스케이스.
 */
final class TalkVisibilityBulkUpdateForStaffAction
{
    public function __construct(
        private readonly TalkVisibilityBulkUpdateForStaffQuery $query,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('update', Talk::class);

        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $isVisible = (bool) $payload['is_visible'];

        return [
            'updated_count' => $this->query->update($ids, $isVisible),
            'is_visible' => $isVisible,
            'ids' => $ids,
        ];
    }
}
