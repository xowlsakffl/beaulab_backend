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
        public ?int $authorId,
        public string $title,
        public string $content,
        public string $status,
        public string $postStatus,
        public bool $isPinned,
        public int $pinnedOrder,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public string $createdAt,
        public string $updatedAt,
        public ?string $nickname,
        public ?int $categoryId,
    ) {}

    public static function fromModel(Talk $talk): self
    {
        return new self(
            id: (int) $talk->id,
            authorId: $talk->author_id ? (int) $talk->author_id : null,
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            postStatus: (string) $talk->post_status,
            isPinned: (bool) $talk->is_pinned,
            pinnedOrder: (int) $talk->pinned_order,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            createdAt: $talk->created_at?->toISOString() ?? '',
            updatedAt: $talk->updated_at?->toISOString() ?? '',
            nickname: self::nickname($talk),
            categoryId: self::categoryId($talk),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'nickname' => $this->nickname,
            'category_id' => $this->categoryId,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'post_status' => $this->postStatus,
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

    private static function nickname(Talk $talk): ?string
    {
        if (! $talk->relationLoaded('author') || ! $talk->author) {
            return null;
        }

        $nickname = trim((string) $talk->author->nickname);

        return $nickname !== '' ? $nickname : (string) $talk->author->name;
    }

    private static function categoryId(Talk $talk): ?int
    {
        if (! $talk->relationLoaded('categories')) {
            return null;
        }

        $id = $talk->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->map(fn (Category $category): int => (int) $category->id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->first();

        return is_int($id) && $id > 0 ? $id : null;
    }
}
