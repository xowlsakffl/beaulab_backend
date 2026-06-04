<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalCreateForStaffQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * HospitalCreateForStaffAction 역할 정의.
 * 병원 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class HospitalCreateForStaffAction
{
    public function __construct(
        private readonly HospitalCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly HospitalBusinessRegistrationCreateForStaffAction $businessRegistrationCreateAction,
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(array $filters): array
    {
        Gate::authorize('create', Hospital::class);

        Log::info('병원 생성', [
            'filters' => $filters,
        ]);

        $hospital = DB::transaction(function () use ($filters) {
            $hospital = $this->query->create([
                ...$filters,
                'email' => mb_strtolower((string) ($filters['email'] ?? '')) ?: null,
            ]);

            $this->mediaAttachAction->attachOne($hospital, $filters['logo'], 'logo', 'hospital', 'logo');
            $this->mediaAttachAction->attachMany($hospital, $filters['gallery'], 'gallery', 'hospital', 'gallery', true);

            $this->businessRegistrationCreateAction->execute($hospital, $filters);
            $this->syncCategories($hospital, $filters['category_ids'] ?? []);
            $this->syncFeatures($hospital, $filters['feature_ids'] ?? []);
            $this->recordInitialStatusHistory($hospital, $filters);

            return $hospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $hospital->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features', 'operationHistories.actor'])
            )->toArray(),
        ];
    }

    private function recordInitialStatusHistory(Hospital $hospital, array $payload): void
    {
        $status = (string) $hospital->status;
        $reason = $status === Hospital::STATUS_ACTIVE ? null : $this->normalizeReason($payload['status_change_reason'] ?? null);
        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $hospital,
            action: OperationHistory::ACTION_STATUS_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            field: 'status',
            beforeValue: null,
            afterValue: $status,
            reason: $reason,
            metadata: [
                'after_label' => $this->statusLabel($status),
                'source' => 'staff.hospital.create',
            ],
        );
    }

    private function normalizeReason(mixed $reason): ?string
    {
        $reason = trim((string) $reason);

        return $reason === '' ? null : $reason;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            Hospital::STATUS_ACTIVE => '정상',
            Hospital::STATUS_SUSPENDED => '운영중지',
            Hospital::STATUS_WITHDRAWN => '탈퇴',
            default => $status,
        };
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Hospital $hospital, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($payload === []) {
            return;
        }

        $hospital->categories()->sync($payload);
    }

    /**
     * @param array<int, int|string> $featureIds
     */
    private function syncFeatures(Hospital $hospital, array $featureIds): void
    {
        if ($featureIds === []) {
            return;
        }

        $payload = collect($featureIds)
            ->map(static fn (int|string $featureId): int => (int) $featureId)
            ->filter(static fn (int $featureId): bool => $featureId > 0)
            ->unique()
            ->values()
            ->all();

        if ($payload === []) {
            return;
        }

        $hospital->features()->sync($payload);
    }
}
