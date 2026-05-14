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
        public array $beforeImages,
        public array $afterImages,
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

    public static function fromModel(HospitalReview $review): self
    {
        return new self(
            id: (int) $review->id,
            createdAt: $review->created_at?->toISOString() ?? '',
            author: self::author($review),
            hospital: self::hospital($review),
            doctor: self::doctor($review),
            categories: self::categories($review),
            beforeImages: self::beforeImages($review),
            afterImages: self::afterImages($review),
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
            'before_images' => $this->beforeImages,
            'after_images' => $this->afterImages,
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function beforeImages(HospitalReview $review): array
    {
        if (! $review->relationLoaded('beforeImages')) {
            return [];
        }

        return self::mediaList($review->beforeImages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function afterImages(HospitalReview $review): array
    {
        if (! $review->relationLoaded('afterImages')) {
            return [];
        }

        return self::mediaList($review->afterImages);
    }

    /**
     * @param  iterable<int, Media>  $mediaList
     * @return array<int, array<string, mixed>>
     */
    private static function mediaList(iterable $mediaList): array
    {
        return collect($mediaList)
            ->map(fn (Media $media): array => [
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
            ])
            ->values()
            ->all();
    }
}
