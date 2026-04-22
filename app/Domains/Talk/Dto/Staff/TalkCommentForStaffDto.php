<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Collection;

/**
 * TalkCommentForStaffDto DTO.
 */
final readonly class TalkCommentForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?string $nickname,
        public ?string $categories,
        public ?string $parentTalkTitle,
        public string $content,
        public string $visibilityStatus,
        public int $likeCount,
        public string $postStatus,
    ) {}

    public static function fromModel(TalkComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            createdAt: $comment->created_at?->toISOString() ?? '',
            nickname: self::nickname($comment),
            categories: self::categoryCode($comment),
            parentTalkTitle: $comment->relationLoaded('talk') && $comment->talk
                ? (string) $comment->talk->title
                : null,
            content: (string) $comment->content,
            visibilityStatus: (string) $comment->status,
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
            'categories' => $this->categories,
            'parent_talk_title' => $this->parentTalkTitle,
            'content' => $this->content,
            'visibility_status' => $this->visibilityStatus,
            'like_count' => $this->likeCount,
            'status' => $this->postStatus,
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

    /**
     */
    private static function categoryCode(TalkComment $comment): ?string
    {
        if (! $comment->relationLoaded('talk') || ! $comment->talk || ! $comment->talk->relationLoaded('categories')) {
            return null;
        }

        $code = $comment->talk->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->map(fn (Category $category): string => (string) $category->code)
            ->filter(static fn (string $code): bool => $code !== '')
            ->first();

        return is_string($code) && $code !== '' ? $code : null;
    }
}
