<?php

namespace App\Domains\HospitalReview\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use App\Domains\Common\Models\Category\Category;
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
        $normalized = $payload;
        $normalized['author_id'] = (int) $user->id;

        $categories = $this->resolveCategories(
            $normalized['category_domain'] ?? null,
            $normalized['category_codes'] ?? [],
        );

        if ($categories->isEmpty()) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '카테고리를 선택해 주세요.');
        }

        $review = DB::transaction(function () use ($normalized, $categories): HospitalReview {
            $review = $this->query->create($normalized);

            $this->syncCategories($review, $categories);
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
