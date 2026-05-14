<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalReview\Models\HospitalReviewComment;

final readonly class HospitalReviewCommentForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?array $author,
        public array $categories,
        public ?array $parent,
        public string $content,
        public string $status,
        public int $likeCount,
    ) {}

    public static function fromModel(HospitalReviewComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            createdAt: $comment->created_at?->toISOString() ?? '',
            author: self::author($comment),
            categories: self::categories($comment),
            parent: self::parent($comment),
            content: (string) $comment->content,
            status: (string) $comment->status,
            likeCount: (int) $comment->like_count,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'author' => $this->author,
            'categories' => $this->categories,
            'parent' => $this->parent,
            'content' => $this->content,
            'status' => $this->status,
            'like_count' => $this->likeCount,
        ];
    }

    private static function author(HospitalReviewComment $comment): ?array
    {
        if (! $comment->relationLoaded('author') || ! $comment->author) {
            return null;
        }

        $attributes = $comment->author->getAttributes();

        return [
            'id' => (int) $comment->author->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function parent(HospitalReviewComment $comment): ?array
    {
        if (! $comment->relationLoaded('review') || ! $comment->review) {
            return null;
        }

        return [
            'id' => (int) $comment->review->id,
            'title' => (string) $comment->review->title,
            'categories' => self::categories($comment),
            'before_images' => self::beforeImages($comment),
            'after_images' => self::afterImages($comment),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(HospitalReviewComment $comment): array
    {
        if (! $comment->relationLoaded('review') || ! $comment->review || ! $comment->review->relationLoaded('categories')) {
            return [];
        }

        return $comment->review->categories
            ->map(static function (Category $category) use ($comment): array {
                $attributes = $category->getAttributes();

                return [
                    'id' => (int) $category->id,
                    'code' => (string) ($attributes['code'] ?? ''),
                    'domain' => (string) ($attributes['domain'] ?? $comment->review->category_domain),
                    'name' => (string) $category->name,
                    'full_path' => (string) ($attributes['full_path'] ?? ''),
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function beforeImages(HospitalReviewComment $comment): array
    {
        if (! $comment->review->relationLoaded('beforeImages')) {
            return [];
        }

        return self::mediaList($comment->review->beforeImages);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function afterImages(HospitalReviewComment $comment): array
    {
        if (! $comment->review->relationLoaded('afterImages')) {
            return [];
        }

        return self::mediaList($comment->review->afterImages);
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
