<?php

namespace App\Domains\Hospital\Actions\Staff;

use App\Domains\HospitalBusinessRegistration\Actions\HospitalBusinessRegistrationCreateForStaffAction;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Hospital\Dto\Staff\HospitalForStaffDetailDto;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Queries\Staff\HospitalCreateForStaffQuery;
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
        private readonly HospitalCreateForStaffQuery                      $query,
        private readonly MediaAttachDeleteAction                          $mediaAttachAction,
        private readonly HospitalBusinessRegistrationCreateForStaffAction $businessRegistrationCreateAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(array $filters): array
    {
        Gate::authorize('create', Hospital::class);

        Log::info('병원 생성', [
            'filters' => array_diff_key($filters, array_flip(['owner_password'])),
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

            return $hospital->fresh();
        });

        return [
            'hospital' => HospitalForStaffDetailDto::fromModel(
                $hospital->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories', 'features']),
                ['business_registration'],
            )->toArray(),
        ];
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
