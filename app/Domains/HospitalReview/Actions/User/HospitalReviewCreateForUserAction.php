<?php

namespace App\Domains\HospitalReview\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalReview\Dto\User\HospitalReviewForUserDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Queries\User\HospitalReviewCreateForUserQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HospitalReviewCreateForUserAction
{
    public function __construct(
        private readonly HospitalReviewCreateForUserQuery $query,
        private readonly MediaAttachDeleteAction $mediaAttachAction,
    ) {}

    public function execute(AccountUser $user, array $payload): array
    {
        if (! empty($payload['doctor_id'])) {
            if (! $this->query->doctorBelongsToHospital((int) $payload['doctor_id'], (int) $payload['hospital_id'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '요청하신 병의원에 소속된 의료진이 아닙니다.');
            }
        }

        $categories = $this->resolveCategories($payload['category_codes'] ?? []);
        $categoryDomain = $this->resolveCategoryDomain($categories);

        $review = DB::transaction(function () use ($user, $payload, $categories, $categoryDomain): HospitalReview {
            $review = $this->query->create([
                ...$payload,
                'author_id' => (int) $user->id,
                'category_domain' => $categoryDomain,
            ]);

            $this->syncCategories($review, $categories);
            $this->attachImages($review, $payload);

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

    /**
     * @param  array<int, string>  $categoryCodes
     * @return Collection<int, Category>
     */
    private function resolveCategories(array $categoryCodes): Collection
    {
        $codes = array_values($categoryCodes);

        $categoriesByCode = $this->query->categoriesByCodes($codes)->keyBy('code');

        return collect($codes)
            ->map(static fn (string $code) => $categoriesByCode->get($code))
            ->filter(static fn ($category): bool => $category instanceof Category)
            ->values();
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function syncCategories(HospitalReview $review, Collection $categories): void
    {
        $categoryIds = $categories
            ->map(static fn (Category $category): int => (int) $category->id)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->values();

        if ($categoryIds->isEmpty()) {
            return;
        }

        $review->categories()->sync(
            $categoryIds
                ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                    $categoryId => ['is_primary' => $index === 0],
                ])
                ->all(),
        );
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function resolveCategoryDomain(Collection $categories): string
    {
        $domain = HospitalReview::categoryDomainFromFullPath(
            $categories->first()?->full_path,
            HospitalReview::categoryRootPathsByDomain(),
        );

        if ($domain === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '후기 카테고리 유형을 확인해 주세요.');
        }

        return $domain;
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
