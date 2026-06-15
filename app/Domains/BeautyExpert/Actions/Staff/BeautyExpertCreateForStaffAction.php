<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDetailDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertCreateForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * BeautyExpertCreateForStaffAction 역할 정의.
 * 뷰티 전문가 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class BeautyExpertCreateForStaffAction
{
    public function __construct(
        private readonly BeautyExpertCreateForStaffQuery $query,
        private readonly MediaAttachDeleteAction         $mediaAttachAction,
        private readonly BeautyExpertUpdateHistoryRecordAction $historyRecordAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', BeautyExpert::class);

        $expert = DB::transaction(function () use ($payload) {
            $expert = $this->query->create($payload);

            $this->mediaAttachAction->attachOne($expert, $payload['profile_image'] ?? null, 'profile_image', 'expert', 'profile-image');
            $this->mediaAttachAction->attachMany($expert, $payload['education_certificate_image'] ?? [], 'education_certificate_image', 'expert', 'education-certificate-image');
            $this->mediaAttachAction->attachMany($expert, $payload['etc_certificate_image'] ?? [], 'etc_certificate_image', 'expert', 'etc-certificate-image');

            $this->syncCategories($expert, $payload['category_ids'] ?? []);

            $expert = $expert->fresh([
                'beauty',
                'profileImage',
                'educationCertificateImages',
                'etcCertificateImages',
                'categories',
            ]);
            $this->historyRecordAction->recordCreated($expert);

            return $expert;
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

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(BeautyExpert $expert, array $categoryIds): void
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

        $expert->categories()->sync($payload);
    }
}
