<?php

namespace App\Domains\Talk\Actions\Staff;

use App\Domains\Common\Actions\AdminActionHistory\AdminActionHistoryCreateAction;
use App\Domains\Common\Models\AdminActionHistory\AdminActionHistory;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Queries\Staff\TalkVisibilityBulkUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * 토크 다중 노출 상태 변경 유스케이스.
 */
final class TalkVisibilityBulkUpdateForStaffAction
{
    public function __construct(
        private readonly TalkVisibilityBulkUpdateForStaffQuery $query,
        private readonly AdminActionHistoryCreateAction $historyCreateAction,
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
        $hiddenReason = $isVisible ? null : $this->normalizeReason($payload['hidden_reason'] ?? null);
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $isVisible, $hiddenReason, $actor): array {
            $talks = $this->query->getForUpdate($ids);
            $existingIds = $talks
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $updatedCount = $this->query->update($existingIds, $isVisible);

            foreach ($talks as $talk) {
                $beforeVisible = (bool) $talk->is_visible;
                if ($beforeVisible === $isVisible) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $talk,
                    action: AdminActionHistory::ACTION_VISIBILITY_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    field: 'is_visible',
                    beforeValue: $beforeVisible,
                    afterValue: $isVisible,
                    reason: $hiddenReason,
                    metadata: [
                        'before_label' => $beforeVisible ? '노출' : '미노출',
                        'after_label' => $isVisible ? '노출' : '미노출',
                        'source' => 'staff.talk.visibility',
                        'bulk' => count($existingIds) > 1,
                    ],
                );
            }

            return [
                'updated_count' => $updatedCount,
                'is_visible' => $isVisible,
                'ids' => $existingIds,
            ];
        });
    }

    private function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }
}
