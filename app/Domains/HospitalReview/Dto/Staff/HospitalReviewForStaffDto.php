<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Collection;

final readonly class HospitalReviewForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?string $nickname,
        public ?string $hospitalName,
        public ?string $doctorName,
        public ?array $category,
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
        public string $postStatus,
    ) {}

    public static function fromModel(HospitalReview $review): self
    {
        return new self(
            id: (int) $review->id,
            createdAt: $review->created_at?->toISOString() ?? '',
            nickname: self::nickname($review),
            hospitalName: $review->relationLoaded('hospital') ? $review->hospital?->name : null,
            doctorName: $review->relationLoaded('doctor') ? $review->doctor?->name : null,
            category: self::category($review),
            beforeImages: self::mediaList($review->relationLoaded('beforeImages') ? $review->beforeImages : collect()),
            afterImages: self::mediaList($review->relationLoaded('afterImages') ? $review->afterImages : collect()),
            cost: (int) $review->cost,
            rating: (int) $review->rating,
            status: (string) $review->status,
            isMainFeatured: (bool) $review->is_main_featured,
            isSubFeatured: (bool) $review->is_sub_featured,
            likeCount: (int) $review->like_count,
            saveCount: (int) $review->save_count,
            commentCount: (int) $review->comment_count,
            viewCount: (int) $review->view_count,
            postStatus: (string) $review->post_status,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'nickname' => $this->nickname,
            'hospital_name' => $this->hospitalName,
            'doctor_name' => $this->doctorName,
            'category' => $this->category,
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
            'post_status' => $this->postStatus,
        ];
    }

    private static function nickname(HospitalReview $review): ?string
    {
        if (! $review->relationLoaded('author') || ! $review->author) {
            return null;
        }

        $nickname = trim((string) $review->author->nickname);

        return $nickname !== '' ? $nickname : (string) $review->author->name;
    }

    private static function category(HospitalReview $review): ?array
    {
        $category = self::resolveCategories($review)
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->first();

        if (! $category instanceof Category) {
            return null;
        }

        return [
            'id' => (int) $category->id,
            'code' => (string) ($category->code ?? ''),
            'name' => (string) $category->name,
            'full_path' => (string) ($category->full_path ?? ''),
        ];
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

        return $review->categories;
    }
}
