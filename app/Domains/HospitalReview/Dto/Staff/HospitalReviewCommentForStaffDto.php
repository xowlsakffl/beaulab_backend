<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
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
        public ?array $report,
        public string $content,
        public string $status,
        public int $likeCount,
    ) {}

    /**
     * @param  array{first_image?: ?Media, image_count?: int}|null  $imageSummary
     */
    public static function fromModel(HospitalReviewComment $comment, ?array $imageSummary = null): self
    {
        return new self(
            id: (int) $comment->id,
            createdAt: $comment->created_at?->toISOString() ?? '',
            author: self::author($comment),
            categories: self::categories($comment),
            parent: self::parent($comment, $imageSummary),
            report: self::report($comment),
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
            'report' => $this->report,
            'content' => $this->content,
            'status' => $this->status,
            'like_count' => $this->likeCount,
        ];
    }

    private static function report(HospitalReviewComment $comment): ?array
    {
        if (! $comment->relationLoaded('contentReportState') || ! $comment->contentReportState) {
            return null;
        }

        $state = $comment->contentReportState;

        if ((string) $state->report_status === ContentReportState::STATUS_NONE) {
            return null;
        }

        return [
            'status' => (string) $state->report_status,
            'label' => $state->statusLabel(),
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

    /**
     * @param  array{first_image?: ?Media, image_count?: int}|null  $imageSummary
     */
    private static function parent(HospitalReviewComment $comment, ?array $imageSummary = null): ?array
    {
        if (! $comment->relationLoaded('review') || ! $comment->review) {
            return null;
        }

        $imageSummary ??= self::relationImageSummary($comment);

        return [
            'id' => (int) $comment->review->id,
            'title' => (string) $comment->review->title,
            'categories' => self::categories($comment),
            'first_image' => self::media($imageSummary['first_image'] ?? null),
            'image_count' => (int) ($imageSummary['image_count'] ?? 0),
            'before_images' => self::mediaCollection(
                $comment->review->relationLoaded('beforeImages') ? $comment->review->beforeImages : collect(),
            ),
            'after_images' => self::mediaCollection(
                $comment->review->relationLoaded('afterImages') ? $comment->review->afterImages : collect(),
            ),
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

    private static function mediaCollection(iterable $mediaItems): array
    {
        $items = [];

        foreach ($mediaItems as $media) {
            $data = self::media($media);

            if ($data !== null) {
                $items[] = $data;
            }
        }

        return $items;
    }

    /**
     * @return array{first_image: ?Media, image_count: int}
     */
    private static function relationImageSummary(HospitalReviewComment $comment): array
    {
        $beforeImages = $comment->review->relationLoaded('beforeImages') ? $comment->review->beforeImages : collect();
        $afterImages = $comment->review->relationLoaded('afterImages') ? $comment->review->afterImages : collect();

        return [
            'first_image' => $beforeImages->first() ?? $afterImages->first(),
            'image_count' => $beforeImages->count() + $afterImages->count(),
        ];
    }
}
