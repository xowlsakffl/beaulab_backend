<?php

namespace App\Domains\HospitalEvaluation\Queries\Staff;

use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Illuminate\Support\Collection;

final class HospitalEvaluationStatusUpdateForStaffQuery
{
    /**
     * @param  array<int, int>  $evaluationIds
     * @return Collection<int, HospitalEvaluation>
     */
    public function getForUpdate(array $evaluationIds): Collection
    {
        $ids = $this->normalizeIds($evaluationIds);

        if ($ids === []) {
            return collect();
        }

        return HospitalEvaluation::query()
            ->whereIn('id', $ids)
            ->with('contentReportState:id,target_type,target_id,report_status')
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'status', 'post_status']);
    }

    /**
     * @param  array<int, int>  $evaluationIds
     */
    public function update(array $evaluationIds, string $status): int
    {
        $ids = $this->normalizeIds($evaluationIds);

        if ($ids === []) {
            return 0;
        }

        return HospitalEvaluation::query()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    /**
     * @param  array<int, int|string>  $evaluationIds
     * @return array<int, int>
     */
    private function normalizeIds(array $evaluationIds): array
    {
        return collect($evaluationIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
