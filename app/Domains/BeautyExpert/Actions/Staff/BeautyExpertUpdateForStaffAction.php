<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDetailDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertUpdateForStaffQuery;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyExpertUpdateForStaffAction 역할 정의.
 * 뷰티 전문가 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyExpertUpdateForStaffAction
{
    public function __construct(
        private readonly BeautyExpertUpdateForStaffQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
        private readonly BeautyExpertUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(BeautyExpert $expert, array $payload): array
    {
        Gate::authorize('update', $expert);
        if (array_key_exists('status', $payload) || array_key_exists('allow_status', $payload)) {
            Gate::authorize('updateStatus', $expert);
        }

        $expert = DB::transaction(function () use ($expert, $payload) {
            $before = $this->historyRecordAction->capture($expert);
            $updated = $this->query->update($expert, $payload);
            $this->replaceMedia($updated, $payload);
            if (array_key_exists('category_ids', $payload) && is_array($payload['category_ids'])) {
                $this->syncCategories($updated, $payload['category_ids']);
            }
            $updated = $updated->fresh([
                'beauty',
                'profileImage',
                'educationCertificateImages',
                'etcCertificateImages',
                'categories',
            ]);
            $this->historyRecordAction->recordUpdated($updated, $before);

            return $updated;
        });

        return [
            'expert' => BeautyExpertForStaffDetailDto::fromModel($expert->load([
                'profileImage',
                'educationCertificateImages',
                'etcCertificateImages',
                'categories',
            ]))->toArray(),
        ];
    }

    private function replaceMedia(BeautyExpert $expert, array $payload): void
    {
        if (($payload['profile_image'] ?? null) instanceof UploadedFile) {
            $this->mediaAttachAction->deleteCollectionMedia($expert, 'profile_image');
            $this->mediaAttachAction->attachOne($expert, $payload['profile_image'], 'profile_image', 'expert', 'profile-image');
        }

        if (array_key_exists('education_certificate_image', $payload) && is_array($payload['education_certificate_image'])) {
            $this->mediaAttachAction->deleteCollectionMedia($expert, 'education_certificate_image');
            $this->mediaAttachAction->attachMany($expert, $this->onlyFiles($payload['education_certificate_image']), 'education_certificate_image', 'expert', 'education-certificate-image');
        }

        if (array_key_exists('etc_certificate_image', $payload) && is_array($payload['etc_certificate_image'])) {
            $this->mediaAttachAction->deleteCollectionMedia($expert, 'etc_certificate_image');
            $this->mediaAttachAction->attachMany($expert, $this->onlyFiles($payload['etc_certificate_image']), 'etc_certificate_image', 'expert', 'etc-certificate-image');
        }
    }

    private function onlyFiles(array $files): array
    {
        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function syncCategories(BeautyExpert $expert, array $categoryIds): void
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

        $expert->categories()->sync($payload);
    }
}
