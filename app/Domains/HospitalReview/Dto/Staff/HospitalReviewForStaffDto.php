<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalReview\Models\HospitalReview;

/**
 * HospitalReviewForStaffDto 역할 정의.
 * 병의원 후기 도메인의 DTO로, 모델 값을 관리자 리스트 응답에 맞는 단순한 구조로 정규화한다.
 */
final readonly class HospitalReviewForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?array $author,
        public ?array $hospital,
        public ?array $doctor,
        public array $categories,
        public ?array $firstImage,
        public int $imageCount,
        public int $cost,
        public int $rating,
        public string $status,
        public bool $isMainFeatured,
        public bool $isSubFeatured,
        public int $likeCount,
        public int $saveCount,
        public int $commentCount,
        public int $viewCount,
    ) {}

    /**
     * @param  array{first_image?: ?Media, image_count?: int}|null  $imageSummary
     */
    public static function fromModel(HospitalReview $review, ?array $imageSummary = null): self
    {
        $imageSummary ??= self::relationImageSummary($review);

        return new self(
            id: (int) $review->id,
            createdAt: $review->created_at?->toISOString() ?? '',
            author: self::author($review),
            hospital: self::hospital($review),
            doctor: self::doctor($review),
            categories: self::categories($review),
            firstImage: self::media($imageSummary['first_image'] ?? null),
            imageCount: (int) ($imageSummary['image_count'] ?? 0),
            cost: (int) $review->cost,
            rating: (int) $review->rating,
            status: (string) $review->status,
            isMainFeatured: (bool) $review->is_main_featured,
            isSubFeatured: (bool) $review->is_sub_featured,
            likeCount: (int) $review->like_count,
            saveCount: (int) $review->save_count,
            commentCount: (int) $review->comment_count,
            viewCount: (int) $review->view_count,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'author' => $this->author,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'categories' => $this->categories,
            'first_image' => $this->firstImage,
            'image_count' => $this->imageCount,
            'cost' => $this->cost,
            'rating' => $this->rating,
            'status' => $this->status,
            'is_main_featured' => $this->isMainFeatured,
            'is_sub_featured' => $this->isSubFeatured,
            'like_count' => $this->likeCount,
            'save_count' => $this->saveCount,
            'comment_count' => $this->commentCount,
            'view_count' => $this->viewCount,
        ];
    }

    private static function author(HospitalReview $review): ?array
    {
        if (! $review->relationLoaded('author') || ! $review->author) {
            return null;
        }

        $attributes = $review->author->getAttributes();

        return [
            'id' => (int) $review->author->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
            'phone' => isset($attributes['phone']) && trim((string) $attributes['phone']) !== ''
                ? (string) $attributes['phone']
                : null,
        ];
    }

    private static function hospital(HospitalReview $review): ?array
    {
        if (! $review->relationLoaded('hospital') || ! $review->hospital) {
            return null;
        }

        $businessNumber = null;
        if ($review->hospital->relationLoaded('businessRegistration') && $review->hospital->businessRegistration) {
            $businessNumber = $review->hospital->businessRegistration->business_number;
        }

        return [
            'id' => (int) $review->hospital->getKey(),
            'name' => (string) $review->hospital->name,
            'business_number' => $businessNumber,
        ];
    }

    private static function doctor(HospitalReview $review): ?array
    {
        if (! $review->relationLoaded('doctor') || ! $review->doctor) {
            return null;
        }

        return [
            'id' => (int) $review->doctor->getKey(),
            'name' => (string) $review->doctor->name,
            'position' => $review->doctor->position,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(HospitalReview $review): array
    {
        if (! $review->relationLoaded('categories')) {
            return [];
        }

        return $review->categories
            ->map(static function (Category $category) use ($review): array {
                $attributes = $category->getAttributes();

                return [
                    'id' => (int) $category->id,
                    'code' => (string) ($attributes['code'] ?? ''),
                    'domain' => (string) ($attributes['domain'] ?? $review->category_domain),
                    'name' => (string) $category->name,
                    'full_path' => (string) ($attributes['full_path'] ?? ''),
                    'parent_id' => $category->parent_id ? (int) $category->parent_id : null,
                    'depth' => isset($attributes['depth']) ? (int) $attributes['depth'] : null,
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ];
            })
            ->values()
            ->all();
    }

    private static function media(?Media $media): ?array
    {
        if (! $media instanceof Media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => (string) $media->mime_type,
            'size' => (int) $media->size,
            'width' => $media->width !== null ? (int) $media->width : null,
            'height' => $media->height !== null ? (int) $media->height : null,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array{first_image: ?Media, image_count: int}
     */
    private static function relationImageSummary(HospitalReview $review): array
    {
        $beforeImages = $review->relationLoaded('beforeImages') ? $review->beforeImages : collect();
        $afterImages = $review->relationLoaded('afterImages') ? $review->afterImages : collect();

        return [
            'first_image' => $beforeImages->first() ?? $afterImages->first(),
            'image_count' => $beforeImages->count() + $afterImages->count(),
        ];
    }
}
