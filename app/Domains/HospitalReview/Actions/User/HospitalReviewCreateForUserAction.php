<?php

namespace App\Domains\HospitalReview\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalReview\Dto\User\HospitalReviewForUserDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\User\HospitalReviewCreateForUserQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * HospitalReviewCreateForUserAction 역할 정의.
 * 병의원 후기 도메인의 Action 계층으로, 사용자 후기 등록 흐름과 도메인 정합성 검증을 조합한다.
 */
final class HospitalReviewCreateForUserAction
{
    public function __construct(
        private readonly HospitalReviewCreateForUserQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        $normalized = $this->normalizePayload($user, $payload);

        $review = DB::transaction(function () use ($normalized): HospitalReview {
            $review = $this->query->create($normalized);

            $this->syncCategories($review, $normalized['resolved_categories']);
            $this->attachImages($review, $normalized);

            return $review->fresh([
                'author',
                'hospital',
                'doctor',
                'categories',
                'beforeImages',
                'afterImages',
            ]);
        });

        return [
            'hospital_review' => HospitalReviewForUserDetailDto::fromModel($review)->toArray(),
        ];
    }

    private function normalizePayload(AccountUser $user, array $payload): array
    {
        $payload['author_id'] = (int) $user->id;

        if (! empty($payload['doctor_id'])) {
            $doctor = HospitalDoctor::query()->find($payload['doctor_id']);

            if (! $doctor || (int) $doctor->hospital_id !== (int) $payload['hospital_id']) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병의원에 소속된 의료진이 아닙니다.');
            }
        }

        $resolvedCategories = $this->resolveCategories(
            $payload['category_domain'] ?? null,
            $payload['category_codes'] ?? [],
        );

        if ($resolvedCategories->isEmpty()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리를 선택해 주세요.');
        }

        $requestedCategoryCount = collect($payload['category_codes'] ?? [])
            ->filter(static fn ($code): bool => is_string($code) && trim($code) !== '')
            ->count();

        if ($resolvedCategories->count() !== $requestedCategoryCount) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '유효한 후기 카테고리만 선택할 수 있습니다.');
        }

        if ($resolvedCategories->contains(static fn (Category $category): bool => (int) ($category->children_count ?? 0) > 0)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '후기 카테고리는 최하위 카테고리만 선택할 수 있습니다.');
        }

        $payload['resolved_categories'] = $resolvedCategories;

        return $payload;
    }

    /**
     * @param array<int, string> $categoryCodes
     * @return Collection<int, Category>
     */
    private function resolveCategories(mixed $categoryDomain, array $categoryCodes): Collection
    {
        if (! is_string($categoryDomain) || ! in_array($categoryDomain, HospitalReview::categoryDomains(), true)) {
            return collect();
        }

        $codes = collect($categoryCodes)
            ->map(static fn ($code): ?string => is_string($code) ? trim($code) : null)
            ->filter(static fn (?string $code): bool => $code !== null && $code !== '')
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return collect();
        }

        $categoriesByCode = Category::query()
            ->where('domain', $categoryDomain)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereIn('code', $codes->all())
            ->withCount('children')
            ->get()
            ->keyBy('code');

        return $codes
            ->map(static fn (string $code) => $categoriesByCode->get($code))
            ->filter(static fn ($category): bool => $category instanceof Category)
            ->values();
    }

    /**
     * @param Collection<int, Category> $categories
     */
    private function syncCategories(HospitalReview $review, Collection $categories): void
    {
        $payload = $categories
            ->values()
            ->mapWithKeys(static fn (Category $category, int $index): array => [
                (int) $category->id => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($payload === []) {
            return;
        }

        $review->categories()->sync($payload);
    }

    private function attachImages(HospitalReview $review, array $payload): void
    {
        $this->mediaAttachAction->attachMany(
            $review,
            $this->onlyFiles($payload['before_images'] ?? null),
            'before_images',
            'hospital-review',
            'before-images',
        );

        $this->mediaAttachAction->attachMany(
            $review,
            $this->onlyFiles($payload['after_images'] ?? null),
            'after_images',
            'hospital-review',
            'after-images',
        );
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function onlyFiles(mixed $files): array
    {
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }
}
