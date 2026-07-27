<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyCreateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyCreateForStaffAction 역할 정의.
 * 뷰티 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyCreateForStaffAction
{
    public function __construct(
        private readonly BeautyCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly BeautyBusinessRegistrationCreateForStaffAction $businessRegistrationCreateAction,
        private readonly BeautyUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /**
     * @return array{beauty: array}
     */
    public function execute(array $filters): array
    {
        Gate::authorize('create', Beauty::class);

        $beauty = DB::transaction(function () use ($filters) {
            $beauty = $this->query->create([
                ...$filters,
                'email' => mb_strtolower((string) ($filters['email'] ?? '')) ?: null,
            ]);

            $this->mediaAttachAction->attachOne($beauty, $filters['logo'], 'logo', 'beauty', 'logo');
            $this->mediaAttachAction->attachMany($beauty, $filters['gallery'], 'gallery', 'beauty', 'gallery', true);

            $this->businessRegistrationCreateAction->execute($beauty, $filters);
            $this->syncCategories($beauty, $filters['category_ids'] ?? []);

            $beauty = $beauty->fresh(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories']);
            $this->historyRecordAction->recordCreated($beauty);

            return $beauty;
        });

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel(
                $beauty->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories'])
            )->toArray(),
        ];
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Beauty $beauty, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($payload === []) {
            return;
        }

        $beauty->categories()->sync($payload);
    }
}
