<?php

namespace App\Domains\Talk\Dto\User;

use App\Domains\Talk\Models\TalkComment;

final readonly class TalkCommentForUserDto
{
    public function __construct(
        public int $id,
        public int $talkId,
        public ?int $parentId,
        public bool $isReply,
        public ?int $authorId,
        public string $content,
        public string $status,
        public string $postStatus,
        public int $likeCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $author = null,
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
            postStatus: (string) $comment->post_status,
            likeCount: (int) $comment->like_count,
            createdAt: $comment->created_at?->toISOString(),
            updatedAt: $comment->updated_at?->toISOString(),
            author: $comment->relationLoaded('author') ? self::author($comment) : null,
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
            'post_status' => $this->postStatus,
            'like_count' => $this->likeCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($this->author !== null) {
            $data['author'] = $this->author;
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
            'nickname' => $comment->author->nickname ? (string) $comment->author->nickname : null,
        ];
    }
}
