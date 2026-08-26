<?php

namespace App\Domains\HospitalVideo\Actions\Staff;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\HospitalVideo\Queries\Staff\HospitalVideoStateUpdateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoAdminStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalVideoStateUpdateForStaffQuery $query,
        private readonly HospitalVideoUpdateHistoryRecordAction $historyRecordAction,
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
            $videos = $this->query->getForUpdate($ids);
            $videos->each(static fn (HospitalVideo $video): mixed => Gate::authorize('updateStatus', $video));

            $existingIds = $videos->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $updatedCount = $this->query->updateAdminStatus($existingIds, $adminStatus);

            foreach ($videos as $video) {
                $beforeStatus = (string) $video->admin_status;
                if ($beforeStatus === $adminStatus) {
                    continue;
                }

                $this->historyRecordAction->recordAdminStatusUpdated(
                    $video,
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

        StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_VIDEO);

        return $result;
    }
}
