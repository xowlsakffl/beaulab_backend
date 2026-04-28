<?php

namespace App\Domains\HospitalReview\Dto\User;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Collection;

/**
 * HospitalReviewForUserDetailDto DTO.
 */
final readonly class HospitalReviewForUserDetailDto
{
    public function __construct(
        public int $id,
        public ?int $authorId,
        public int $hospitalId,
        public ?int $doctorId,
        public string $categoryDomain,
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
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $author = null,
        public ?array $hospital = null,
        public ?array $doctor = null,
        public ?array $categories = null,
        public ?array $beforeImages = null,
        public ?array $afterImages = null,
    ) {}

    public static function fromModel(HospitalReview $review): self
    {
        return new self(
            id: (int) $review->id,
            authorId: $review->author_id ? (int) $review->author_id : null,
            hospitalId: (int) $review->hospital_id,
            doctorId: $review->doctor_id ? (int) $review->doctor_id : null,
            categoryDomain: (string) $review->category_domain,
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
            createdAt: $review->created_at?->toISOString(),
            updatedAt: $review->updated_at?->toISOString(),
            author: $review->relationLoaded('author') ? self::author($review) : null,
            hospital: $review->relationLoaded('hospital') ? self::hospital($review) : null,
            doctor: $review->relationLoaded('doctor') ? self::doctor($review) : null,
            categories: $review->relationLoaded('categories') ? self::categories($review) : null,
            beforeImages: $review->relationLoaded('beforeImages') ? self::mediaList($review->beforeImages) : null,
            afterImages: $review->relationLoaded('afterImages') ? self::mediaList($review->afterImages) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'hospital_id' => $this->hospitalId,
            'doctor_id' => $this->doctorId,
            'category_domain' => $this->categoryDomain,
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->author !== null) {
            $data['author'] = $this->author;
        }

        if ($this->hospital !== null) {
            $data['hospital'] = $this->hospital;
        }

        if ($this->doctor !== null) {
            $data['doctor'] = $this->doctor;
        }

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        if ($this->beforeImages !== null) {
            $data['before_images'] = $this->beforeImages;
        }

        if ($this->afterImages !== null) {
            $data['after_images'] = $this->afterImages;
        }

        return $data;
    }

    private static function author(HospitalReview $review): ?array
    {
        if (! $review->relationLoaded('author') || ! $review->author) {
            return null;
        }

        return [
            'id' => (int) $review->author->id,
            'name' => (string) $review->author->name,
            'nickname' => $review->author->nickname ? (string) $review->author->nickname : null,
        ];
    }

    private static function hospital(HospitalReview $review): ?array
    {
        if (! $review->relationLoaded('hospital') || ! $review->hospital) {
            return null;
        }

        return [
            'id' => (int) $review->hospital->id,
            'name' => (string) $review->hospital->name,
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
        ];
    }

    private static function categories(HospitalReview $review): array
    {
        return self::resolveCategories($review)
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'code' => (string) ($category->code ?? ''),
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Media> $mediaList
     * @return array<int, array<string, mixed>>
     */
    private static function mediaList(Collection $mediaList): array
    {
        return $mediaList
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

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(HospitalReview $review): Collection
    {
        if (! $review->relationLoaded('categories')) {
            return collect();
        }

        return $review->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->values();
    }
}
