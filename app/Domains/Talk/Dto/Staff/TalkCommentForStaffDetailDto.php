<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;
use Illuminate\Support\Collection;

/**
 * TalkCommentForStaffDetailDto 역할 정의.
 * 토크 댓글 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class TalkCommentForStaffDetailDto
{
    public function __construct(
        public int $id,
        public int $talkId,
        public ?int $parentId,
        public bool $isReply,
        public ?int $authorId,
        public string $content,
        public string $status,
        public ?string $authorIp,
        public int $likeCount,
        public int $mentionCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
        public ?array $author = null,
        public ?array $talk = null,
        public ?array $mentions = null,
        public ?array $children = null,
    ) {}

    public static function fromModel(TalkComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            talkId: (int) $comment->talk_id,
            parentId: $comment->parent_id ? (int) $comment->parent_id : null,
            isReply: $comment->isReply(),
            authorId: $comment->author_id ? (int) $comment->author_id : null,
            content: (string) $comment->content,
            status: (string) $comment->status,
            authorIp: $comment->author_ip,
            likeCount: (int) $comment->like_count,
            mentionCount: (int) ($comment->mentions_count ?? $comment->mentions()->count()),
            createdAt: $comment->created_at?->toISOString(),
            updatedAt: $comment->updated_at?->toISOString(),
            deletedAt: $comment->deleted_at?->toISOString(),
            author: $comment->relationLoaded('author') ? self::author($comment) : null,
            talk: $comment->relationLoaded('talk') ? self::talk($comment) : null,
            mentions: $comment->relationLoaded('mentions') ? self::mentions($comment) : null,
            children: $comment->relationLoaded('children') && $comment->isRootComment() ? self::children($comment) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'talk_id' => $this->talkId,
            'parent_id' => $this->parentId,
            'is_reply' => $this->isReply,
            'author_id' => $this->authorId,
            'content' => $this->content,
            'status' => $this->status,
            'author_ip' => $this->authorIp,
            'like_count' => $this->likeCount,
            'mention_count' => $this->mentionCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        if ($this->author !== null) {
            $data['author'] = $this->author;
        }

        if ($this->talk !== null) {
            $data['talk'] = $this->talk;
        }

        if ($this->mentions !== null) {
            $data['mentions'] = $this->mentions;
        }

        if ($this->children !== null) {
            $data['children'] = $this->children;
        }

        return $data;
    }

    private static function author(TalkComment $comment): ?array
    {
        if (! $comment->relationLoaded('author') || ! $comment->author) {
            return null;
        }

        return [
            'id' => (int) $comment->author->id,
            'name' => (string) $comment->author->name,
            'email' => (string) $comment->author->email,
        ];
    }

    private static function talk(TalkComment $comment): ?array
    {
        if (! $comment->relationLoaded('talk') || ! $comment->talk) {
            return null;
        }

        return [
            'id' => (int) $comment->talk->id,
            'title' => (string) $comment->talk->title,
        ];
    }

    private static function mentions(TalkComment $comment): array
    {
        return self::resolveMentions($comment)
            ->map(fn (TalkCommentMention $mention): array => [
                'id' => (int) $mention->id,
                'mentioned_user_id' => (int) $mention->mentioned_user_id,
                'mentioned_user_name' => $mention->relationLoaded('mentionedUser') && $mention->mentionedUser
                    ? (string) $mention->mentionedUser->name
                    : null,
                'mentioned_by_user_id' => $mention->mentioned_by_user_id ? (int) $mention->mentioned_by_user_id : null,
                'mention_text' => $mention->mention_text,
                'start_offset' => $mention->start_offset,
                'end_offset' => $mention->end_offset,
            ])
            ->values()
            ->all();
    }

    private static function children(TalkComment $comment): array
    {
        return self::resolveChildren($comment)
            ->map(fn (TalkComment $child): array => [
                'id' => (int) $child->id,
                'parent_id' => $child->parent_id ? (int) $child->parent_id : null,
                'is_reply' => $child->isReply(),
                'author_id' => $child->author_id ? (int) $child->author_id : null,
                'author_name' => $child->relationLoaded('author') && $child->author
                    ? (string) $child->author->name
                    : null,
                'content' => (string) $child->content,
                'status' => (string) $child->status,
                'like_count' => (int) $child->like_count,
                'created_at' => $child->created_at?->toISOString(),
                'updated_at' => $child->updated_at?->toISOString(),
                'deleted_at' => $child->deleted_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, TalkComment>
     */
    private static function resolveChildren(TalkComment $comment): Collection
    {
        if (! $comment->relationLoaded('children')) {
            return collect();
        }

        return $comment->children;
    }

    /**
     * @return Collection<int, TalkCommentMention>
     */
    private static function resolveMentions(TalkComment $comment): Collection
    {
        if (! $comment->relationLoaded('mentions')) {
            return collect();
        }

        return $comment->mentions;
    }
}
