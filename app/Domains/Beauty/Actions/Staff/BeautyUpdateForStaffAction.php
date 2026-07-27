<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyUpdateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyUpdateForStaffAction 역할 정의.
 * 뷰티 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyUpdateForStaffAction
{
    public function __construct(
        private readonly BeautyUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly BeautyBusinessRegistrationUpdateForStaffAction $businessRegistrationUpdateAction,
        private readonly BeautyUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    /**
     * @return array{beauty: array}
     */
    public function execute(Beauty $beauty, array $payload): array
    {
        Gate::authorize('update', $beauty);

        $updated = DB::transaction(function () use ($beauty, $payload) {
            $before = $this->historyRecordAction->capture($beauty);
            $updatedBeauty = $this->query->update($beauty, $payload);

            $this->replaceMedia($updatedBeauty, $payload);
            $this->businessRegistrationUpdateAction->execute($updatedBeauty, $payload);
            if (array_key_exists('category_ids', $payload) && is_array($payload['category_ids'])) {
                $this->syncCategories($updatedBeauty, $payload['category_ids']);
            }

            $updatedBeauty = $updatedBeauty->fresh(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories']);
            $this->historyRecordAction->recordUpdated($updatedBeauty, $before);

            return $updatedBeauty;
        });

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel(
                $updated->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories'])
            )->toArray(),
        ];
    }

    private function replaceMedia(Beauty $beauty, array $payload): void
    {
        if (isset($payload['logo']) && $payload['logo'] instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($beauty, 'logo');
            $this->mediaAttachAction->attachOne($beauty, $payload['logo'], 'logo', 'beauty', 'logo');
        }

        if (isset($payload['gallery']) && is_array($payload['gallery'])) {
            $galleryFiles = array_values(array_filter(
                $payload['gallery'],
                static fn ($file): bool => $file instanceof UploadedFile,
            ));

            if ($galleryFiles !== []) {
                $this->mediaAttachAction->deleteCollectionMedia($beauty, 'gallery');
                $this->mediaAttachAction->attachMany($beauty, $galleryFiles, 'gallery', 'beauty', 'gallery', true);
            }
        }
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Beauty $beauty, array $categoryIds): void
    {
        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        $beauty->categories()->sync($payload);
    }
}
