<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;

/**
 * TalkCommentForStaffDto DTO.
 */
final readonly class TalkCommentForStaffDto
{
    public function __construct(
        public int $id,
        public string $createdAt,
        public ?array $author,
        public ?array $category,
        public ?array $mention,
        public ?array $report,
        public ?string $parentTalkTitle,
        public string $content,
        public string $status,
        public int $likeCount,
    ) {}

    public static function fromModel(TalkComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            createdAt: $comment->created_at?->toISOString() ?? '',
            author: self::author($comment),
            category: self::category($comment),
            mention: self::mention($comment),
            report: self::report($comment),
            parentTalkTitle: $comment->relationLoaded('talk') && $comment->talk
                ? (string) $comment->talk->title
                : null,
            content: (string) $comment->content,
            status: (string) $comment->status,
            likeCount: (int) $comment->like_count,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'author' => $this->author,
            'category' => $this->category,
            'mention' => $this->mention,
            'report' => $this->report,
            'parent_talk_title' => $this->parentTalkTitle,
            'content' => $this->content,
            'status' => $this->status,
            'like_count' => $this->likeCount,
        ];

        return $data;
    }

    private static function author(TalkComment $comment): ?array
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
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function category(TalkComment $comment): ?array
    {
        if (! $comment->relationLoaded('talk') || ! $comment->talk || ! $comment->talk->relationLoaded('categories')) {
            return null;
        }

        $category = $comment->talk->categories
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

    private static function mention(TalkComment $comment): ?array
    {
        if (! $comment->relationLoaded('mentions')) {
            return null;
        }

        $mention = $comment->mentions->first();

        if (! $mention instanceof TalkCommentMention) {
            return null;
        }

        return [
            'id' => (int) $mention->id,
            'mentioned_user_id' => (int) $mention->mentioned_user_id,
            'mentioned_user_name' => $mention->relationLoaded('mentionedUser') && $mention->mentionedUser
                ? (string) $mention->mentionedUser->name
                : null,
            'mentioned_by_user_id' => $mention->mentioned_by_user_id ? (int) $mention->mentioned_by_user_id : null,
            'mention_text' => $mention->mention_text,
        ];
    }

    private static function report(TalkComment $comment): ?array
    {
        if (! $comment->relationLoaded('contentReportState') || ! $comment->contentReportState) {
            return null;
        }

        $state = $comment->contentReportState;

        if ((string) $state->report_status === ContentReportState::STATUS_NONE) {
            return null;
        }

        return [
            'status' => (string) $state->report_status,
            'label' => $state->statusLabel(),
        ];
    }
}
