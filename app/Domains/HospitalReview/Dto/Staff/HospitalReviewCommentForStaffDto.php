<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalReview\Models\HospitalReviewComment;

final readonly class HospitalReviewCommentForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?array $author,
        public array $categories,
        public ?string $parentHospitalReviewTitle,
        public string $content,
        public string $status,
        public int $likeCount,
        public string $postStatus,
    ) {}

    public static function fromModel(HospitalReviewComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            createdAt: $comment->created_at?->toISOString() ?? '',
            author: self::author($comment),
            categories: self::categories($comment),
            parentHospitalReviewTitle: $comment->relationLoaded('review') && $comment->review
                ? (string) $comment->review->title
                : null,
            content: (string) $comment->content,
            status: (string) $comment->status,
            likeCount: (int) $comment->like_count,
            postStatus: (string) $comment->post_status,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'author' => $this->author,
            'categories' => $this->categories,
            'parent_hospital_review_title' => $this->parentHospitalReviewTitle,
            'content' => $this->content,
            'status' => $this->status,
            'like_count' => $this->likeCount,
            'post_status' => $this->postStatus,
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
}
