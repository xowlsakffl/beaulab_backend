<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventStateUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventAdminStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventStateUpdateForStaffQuery $query,
        private readonly HospitalEventUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $adminStatus = (string) $payload['admin_status'];

        $result = DB::transaction(function () use ($ids, $adminStatus, $payload): array {
            $events = $this->query->getForUpdate($ids);
            $events->each(static fn (HospitalEvent $event): mixed => Gate::authorize('update', $event));

            $existingIds = $events->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAdminStatus($existingIds, $adminStatus);

            foreach ($events as $event) {
                $beforeStatus = (string) $event->admin_status;
                if ($beforeStatus === $adminStatus) {
                    continue;
                }

                $this->historyRecordAction->recordAdminStatusUpdated(
                    $event,
                    $beforeStatus,
                    $adminStatus,
                    $payload['reason'] ?? null,
                    count($existingIds) > 1,
                );
            }

            return [
                'updated_count' => $updatedCount,
                'admin_status' => $adminStatus,
                'ids' => $existingIds,
            ];
        });

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_EVENT);

        return $result;
    }
}
