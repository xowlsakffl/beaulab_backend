<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Models\Talk;

/**
 * TalkForStaffDto 역할 정의.
 * 토크 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class TalkForStaffDto
{
    public function __construct(
        public int $id,
        public ?array $author,
        public ?array $category,
        public string $title,
        public string $content,
        public string $status,
        public bool $isPinned,
        public int $pinnedOrder,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(Talk $talk): self
    {
        return new self(
            id: (int) $talk->id,
            author: self::author($talk),
            category: self::category($talk),
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            isPinned: (bool) $talk->is_pinned,
            pinnedOrder: (int) $talk->pinned_order,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            createdAt: $talk->created_at?->toISOString() ?? '',
            updatedAt: $talk->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author' => $this->author,
            'category' => $this->category,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'is_pinned' => $this->isPinned,
            'pinned_order' => $this->pinnedOrder,
            'view_count' => $this->viewCount,
            'comment_count' => $this->commentCount,
            'like_count' => $this->likeCount,
            'save_count' => $this->saveCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function author(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('author') || ! $talk->author) {
            return null;
        }

        $attributes = $talk->author->getAttributes();

        return [
            'id' => (int) $talk->author->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function category(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('categories')) {
            return null;
        }

        $category = $talk->categories
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
            'domain' => (string) ($attributes['domain'] ?? Talk::CATEGORY_DOMAIN),
            'name' => (string) $category->name,
            'full_path' => (string) ($attributes['full_path'] ?? ''),
            'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
        ];
    }

}
