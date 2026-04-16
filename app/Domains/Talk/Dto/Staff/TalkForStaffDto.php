<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\Talk;
use Illuminate\Support\Collection;

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
        public bool $isVisible,
        public bool $isPinned,
        public int $pinnedOrder,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public string $createdAt,
        public string $updatedAt,
        public ?array $author,
        public ?array $categories,
    ) {}

    public static function fromModel(Talk $talk): self
    {
        return new self(
            id: (int) $talk->id,
            authorId: $talk->author_id ? (int) $talk->author_id : null,
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            isVisible: (bool) $talk->is_visible,
            isPinned: (bool) $talk->is_pinned,
            pinnedOrder: (int) $talk->pinned_order,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            createdAt: $talk->created_at?->toISOString() ?? '',
            updatedAt: $talk->updated_at?->toISOString() ?? '',
            author: $talk->relationLoaded('author') && $talk->author
                ? [
                    'id' => (int) $talk->author->id,
                    'name' => (string) $talk->author->name,
                    'nickname' => (string) $talk->author->name,
                    'email' => (string) $talk->author->email,
                ]
                : null,
            categories: $talk->relationLoaded('categories')
                ? self::resolveCategories($talk)
                    ->map(fn (Category $category): array => [
                        'id' => (int) $category->id,
                        'name' => (string) $category->name,
                        'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                    ])
                    ->values()
                    ->all()
                : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author_id' => $this->authorId,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'is_visible' => $this->isVisible,
            'is_pinned' => $this->isPinned,
            'pinned_order' => $this->pinnedOrder,
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

        if ($this->categories !== null) {
            $data['categories'] = $this->categories;
        }

        return $data;
    }

    /**
     * @return Collection<int, Category>
     */
    private static function resolveCategories(Talk $talk): Collection
    {
        if (! $talk->relationLoaded('categories')) {
            return collect();
        }

        return $talk->categories;
    }
}
