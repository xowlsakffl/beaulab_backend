<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Support\Collection;

/**
 * TalkCommentForStaffDto 역할 정의.
 * 토크 댓글 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class TalkCommentForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?string $nickname,
        public array $talkType,
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
            talkType: self::talkTypeCodes($comment)->values()->all(),
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
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'nickname' => $this->nickname,
            'talk_type' => $this->talkType,
            'parent_talk_title' => $this->parentTalkTitle,
            'content' => $this->content,
            'visibility_status' => $this->visibilityStatus,
            'like_count' => $this->likeCount,
            'status' => $this->postStatus,
        ];
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
     * @return Collection<int, string>
     */
    private static function talkTypeCodes(TalkComment $comment): Collection
    {
        if (! $comment->relationLoaded('talk') || ! $comment->talk || ! $comment->talk->relationLoaded('categories')) {
            return collect();
        }

        return $comment->talk->categories
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->map(fn (Category $category): string => (string) $category->code)
            ->filter(static fn (string $code): bool => $code !== '')
            ->values();
    }
}
