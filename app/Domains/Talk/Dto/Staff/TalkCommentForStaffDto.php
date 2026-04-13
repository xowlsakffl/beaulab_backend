<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;
use Illuminate\Support\Collection;

/**
 * TalkCommentForStaffDto 역할 정의.
 * 토크 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class TalkCommentForStaffDto
{
    public function __construct(
        public int $id,
        public int $talkId,
        public ?int $parentId,
        public bool $isReply,
        public ?int $authorId,
        public int $mentionCount,
        public string $content,
        public string $status,
        public bool $isVisible,
        public int $likeCount,
        public string $createdAt,
        public string $updatedAt,
        public ?array $author,
        public ?array $talk,
        public ?array $mentions,
    ) {}

    public static function fromModel(TalkComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            talkId: (int) $comment->talk_id,
            parentId: $comment->parent_id ? (int) $comment->parent_id : null,
            isReply: $comment->isReply(),
            authorId: $comment->author_id ? (int) $comment->author_id : null,
            mentionCount: (int) ($comment->mentions_count ?? 0),
            content: (string) $comment->content,
            status: (string) $comment->status,
            isVisible: (bool) $comment->is_visible,
            likeCount: (int) $comment->like_count,
            createdAt: $comment->created_at?->toISOString() ?? '',
            updatedAt: $comment->updated_at?->toISOString() ?? '',
            author: $comment->relationLoaded('author') && $comment->author
                ? [
                    'id' => (int) $comment->author->id,
                    'name' => (string) $comment->author->name,
                    'email' => (string) $comment->author->email,
                ]
                : null,
            talk: $comment->relationLoaded('talk') && $comment->talk
                ? [
                    'id' => (int) $comment->talk->id,
                    'title' => (string) $comment->talk->title,
                ]
                : null,
            mentions: $comment->relationLoaded('mentions')
                ? self::resolveMentions($comment)
                    ->map(fn (TalkCommentMention $mention): array => [
                        'id' => (int) $mention->id,
                        'mentioned_user_id' => (int) $mention->mentioned_user_id,
                        'mentioned_user_name' => $mention->relationLoaded('mentionedUser') && $mention->mentionedUser
                            ? (string) $mention->mentionedUser->name
                            : null,
                        'mention_text' => $mention->mention_text,
                        'start_offset' => $mention->start_offset,
                        'end_offset' => $mention->end_offset,
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
            'talk_id' => $this->talkId,
            'parent_id' => $this->parentId,
            'is_reply' => $this->isReply,
            'author_id' => $this->authorId,
            'mention_count' => $this->mentionCount,
            'content' => $this->content,
            'status' => $this->status,
            'is_visible' => $this->isVisible,
            'like_count' => $this->likeCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
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

        return $data;
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
