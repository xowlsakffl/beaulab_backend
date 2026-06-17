<?php

namespace App\Domains\HospitalEvent\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use App\Domains\HospitalEvent\Queries\Staff\HospitalEventConsultationStatusUpdateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class HospitalEventConsultationStatusUpdateForStaffAction
{
    public function __construct(
        private readonly HospitalEventConsultationStatusUpdateForStaffQuery $query,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    public function execute(array $payload): array
    {
        $ids = collect($payload['ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
        $status = (string) $payload['status'];
        $actor = auth()->user();

        return DB::transaction(function () use ($ids, $status, $payload, $actor): array {
            $consultations = $this->query->getForUpdate($ids);
            $consultations->each(static function (HospitalEventConsultation $consultation): void {
                if ($consultation->event !== null) {
                    Gate::authorize('update', $consultation->event);
                }
            });

            if ($consultations->isEmpty()) {
                return [
                    'updated_count' => 0,
                    'status' => $status,
                    'ids' => [],
                ];
            }

            $existingIds = $consultations->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all();
            $values = $this->statusUpdateValues($status);
            $updatedCount = $this->query->updateStatus($existingIds, $values);

            foreach ($consultations as $consultation) {
                $changes = OperationHistoryChangeSetBuilder::single(
                        key: 'status',
                        label: '상담여부',
                        before: $consultation->status,
                        after: $status,
                        beforeDisplay: HospitalEventConsultation::statusLabel($consultation->status),
                        afterDisplay: HospitalEventConsultation::statusLabel($status),
                    );

                if ($changes === []) {
                    continue;
                }

                $this->historyCreateAction->execute(
                    target: $consultation,
                    action: OperationHistory::ACTION_STATUS_UPDATED,
                    actor: $actor instanceof Model ? $actor : null,
                    reason: $payload['reason'] ?? null,
                    metadata: [
                        'source' => 'staff.hospital_event_consultation.status',
                        'bulk' => count($existingIds) > 1,
                    ],
                    changes: $changes,
                );
            }

            return [
                'updated_count' => $updatedCount,
                'status' => $status,
                'ids' => $existingIds,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function statusUpdateValues(string $status): array
    {
        $now = Carbon::now();

        return match ($status) {
            HospitalEventConsultation::STATUS_CONFIRMED => [
                'status' => $status,
                'contacted_at' => $now,
                'confirmed_at' => $now,
                'duplicated_at' => null,
            ],
            HospitalEventConsultation::STATUS_DUPLICATE => [
                'status' => $status,
                'contacted_at' => $now,
                'confirmed_at' => null,
                'duplicated_at' => $now,
            ],
            default => [
                'status' => HospitalEventConsultation::STATUS_NEW,
                'contacted_at' => null,
                'confirmed_at' => null,
                'duplicated_at' => null,
            ],
        };
    }
}
