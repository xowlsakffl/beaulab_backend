<?php

namespace App\Domains\HospitalReview\Dto\User;

use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Models\HospitalReviewCommentMention;

final readonly class HospitalReviewCommentForUserDto
{
    public function __construct(
        public int $id,
        public int $hospitalReviewId,
        public ?int $parentId,
        public bool $isReply,
        public string $content,
        public string $status,
        public string $postStatus,
        public int $likeCount,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?array $author = null,
        public ?array $mention = null,
    ) {}

    public static function fromModel(HospitalReviewComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            hospitalReviewId: (int) $comment->hospital_review_id,
            parentId: $comment->parent_id ? (int) $comment->parent_id : null,
            isReply: $comment->isReply(),
            content: (string) $comment->content,
            status: (string) $comment->status,
            postStatus: (string) $comment->post_status,
            likeCount: (int) $comment->like_count,
            createdAt: $comment->created_at?->toISOString(),
            updatedAt: $comment->updated_at?->toISOString(),
            author: $comment->relationLoaded('author') ? self::author($comment) : null,
            mention: $comment->relationLoaded('mentions') ? self::mention($comment) : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'hospital_review_id' => $this->hospitalReviewId,
            'parent_id' => $this->parentId,
            'is_reply' => $this->isReply,
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

        if ($this->mention !== null) {
            $data['mention'] = $this->mention;
        }

        return $data;
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
        ];
    }

    private static function mention(HospitalReviewComment $comment): ?array
    {
        $mention = $comment->mentions->first();

        if (! $mention instanceof HospitalReviewCommentMention) {
            return null;
        }

        return [
            'id' => (int) $mention->id,
            'mentioned_user_id' => (int) $mention->mentioned_user_id,
            'mentioned_by_user_id' => $mention->mentioned_by_user_id ? (int) $mention->mentioned_by_user_id : null,
            'mention_text' => $mention->mention_text,
            'start_offset' => $mention->start_offset,
            'end_offset' => $mention->end_offset,
            'mentioned_user' => $mention->relationLoaded('mentionedUser') && $mention->mentionedUser
                ? [
                    'id' => (int) $mention->mentionedUser->id,
                    'name' => (string) $mention->mentionedUser->name,
                    'nickname' => $mention->mentionedUser->nickname ? (string) $mention->mentionedUser->nickname : null,
                ]
                : null,
        ];
    }
}
