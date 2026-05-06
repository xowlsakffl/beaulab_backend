<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalReview\Models\HospitalReview;

final readonly class HospitalReviewForStaffDetailDto
{
    public function __construct(
        public int $id,
        public ?array $author,
        public ?array $hospital,
        public ?array $doctor,
        public ?array $category,
        public string $title,
        public string $content,
        public int $cost,
        public int $rating,
        public string $status,
        public string $postStatus,
        public bool $isMainFeatured,
        public bool $isSubFeatured,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public array $beforeImages,
        public array $afterImages,
        public array $operationHistories,
        public array $comments,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(
        HospitalReview $review,
        array $operationHistories,
        array $comments,
    ): self
    {
        return new self(
            id: (int) $review->id,
            author: self::author($review),
            hospital: self::hospital($review),
            doctor: self::doctor($review),
            category: self::category($review),
            title: (string) $review->title,
            content: (string) $review->content,
            cost: (int) $review->cost,
            rating: (int) $review->rating,
            status: (string) $review->status,
            postStatus: (string) $review->post_status,
            isMainFeatured: (bool) $review->is_main_featured,
            isSubFeatured: (bool) $review->is_sub_featured,
            viewCount: (int) $review->view_count,
            commentCount: (int) $review->comment_count,
            likeCount: (int) $review->like_count,
            saveCount: (int) $review->save_count,
            beforeImages: self::beforeImages($review),
            afterImages: self::afterImages($review),
            operationHistories: $operationHistories,
            comments: $comments,
            createdAt: $review->created_at?->toISOString(),
            updatedAt: $review->updated_at?->toISOString(),
            deletedAt: $review->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'category' => $this->category,
            'title' => $this->title,
            'content' => $this->content,
            'cost' => $this->cost,
            'rating' => $this->rating,
            'status' => $this->status,
            'post_status' => $this->postStatus,
            'is_main_featured' => $this->isMainFeatured,
            'is_sub_featured' => $this->isSubFeatured,
            'view_count' => $this->viewCount,
            'comment_count' => $this->commentCount,
            'like_count' => $this->likeCount,
            'save_count' => $this->saveCount,
            'before_images' => $this->beforeImages,
            'after_images' => $this->afterImages,
            'operation_histories' => $this->operationHistories,
            'comments' => $this->comments,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
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
            'id' => (int) $review->hospital->id,
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
            'id' => (int) $review->doctor->id,
            'name' => (string) $review->doctor->name,
            'position' => $review->doctor->position,
        ];
    }

    private static function category(HospitalReview $review): ?array
    {
        if (! $review->relationLoaded('categories')) {
            return null;
        }

        $category = $review->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->values()
            ->first();

        if (! $category instanceof Category) {
            return null;
        }

        $attributes = $category->getAttributes();

        return [
            'id' => (int) $category->id,
            'code' => (string) ($attributes['code'] ?? ''),
            'domain' => (string) ($attributes['domain'] ?? $review->category_domain),
            'name' => (string) $category->name,
            'full_path' => (string) ($attributes['full_path'] ?? ''),
            'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
        ];
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
     * @param iterable<int, Media> $mediaList
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
