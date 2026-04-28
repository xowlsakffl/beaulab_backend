<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Models\TalkComment;

/**
 * TalkCommentForStaffDto DTO.
 */
final readonly class TalkCommentForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?string $nickname,
        public ?int $categoryId,
        public ?string $parentTalkTitle,
        public string $content,
        public string $status,
        public int $likeCount,
        public string $postStatus,
    ) {}

    public static function fromModel(TalkComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            createdAt: $comment->created_at?->toISOString() ?? '',
            nickname: self::nickname($comment),
            categoryId: self::categoryId($comment),
            parentTalkTitle: $comment->relationLoaded('talk') && $comment->talk
                ? (string) $comment->talk->title
                : null,
            content: (string) $comment->content,
            status: (string) $comment->status,
            likeCount: (int) $comment->like_count,
            postStatus: (string) $comment->post_status,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'nickname' => $this->nickname,
            'category_id' => $this->categoryId,
            'parent_talk_title' => $this->parentTalkTitle,
            'content' => $this->content,
            'status' => $this->status,
            'like_count' => $this->likeCount,
            'post_status' => $this->postStatus,
        ];

        return $data;
    }

    private static function nickname(TalkComment $comment): ?string
    {
        if (! $comment->relationLoaded('author') || ! $comment->author) {
            return null;
        }

        $nickname = trim((string) $comment->author->nickname);

        return $nickname !== '' ? $nickname : (string) $comment->author->name;
    }

    private static function categoryId(TalkComment $comment): ?int
    {
        if (! $comment->relationLoaded('talk') || ! $comment->talk || ! $comment->talk->relationLoaded('categories')) {
            return null;
        }

        $id = $comment->talk->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->map(fn (Category $category): int => (int) $category->id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->first();

        return is_int($id) && $id > 0 ? $id : null;
    }
}
