<?php

namespace App\Domains\Talk\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;
use App\Domains\Talk\Models\TalkPollOption;

/**
 * TalkForStaffDetailDto 역할 정의.
 * 토크 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class TalkForStaffDetailDto
{
    public function __construct(
        public int $id,
        public ?array $author,
        public ?array $category,
        public ?array $report,
        public string $title,
        public string $content,
        public string $status,
        public ?string $authorIp,
        public bool $isPinned,
        public int $pinnedOrder,
        public int $viewCount,
        public int $commentCount,
        public int $likeCount,
        public int $saveCount,
        public array $images,
        public ?array $poll,
        public ?array $operationHistories,
        public ?array $comments,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(
        Talk $talk,
        ?array $operationHistories = null,
        ?array $comments = null,
    ): self {
        return new self(
            id: (int) $talk->id,
            author: self::author($talk),
            category: self::category($talk),
            report: self::report($talk),
            title: (string) $talk->title,
            content: (string) $talk->content,
            status: (string) $talk->status,
            authorIp: $talk->author_ip,
            isPinned: (bool) $talk->is_pinned,
            pinnedOrder: (int) $talk->pinned_order,
            viewCount: (int) $talk->view_count,
            commentCount: (int) $talk->comment_count,
            likeCount: (int) $talk->like_count,
            saveCount: (int) $talk->save_count,
            images: self::images($talk),
            poll: self::poll($talk),
            operationHistories: $operationHistories,
            comments: $comments,
            createdAt: $talk->created_at?->toISOString(),
            updatedAt: $talk->updated_at?->toISOString(),
            deletedAt: $talk->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author' => $this->author,
            'category' => $this->category,
            'report' => $this->report,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'author_ip' => $this->authorIp,
            'is_pinned' => $this->isPinned,
            'pinned_order' => $this->pinnedOrder,
            'view_count' => $this->viewCount,
            'comment_count' => $this->commentCount,
            'like_count' => $this->likeCount,
            'save_count' => $this->saveCount,
            'images' => $this->images,
            'poll' => $this->poll,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        if ($this->operationHistories !== null) {
            $data['operation_histories'] = $this->operationHistories;
        }

        if ($this->comments !== null) {
            $data['comments'] = $this->comments;
        }

        return $data;
    }

    private static function author(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('author') || ! $talk->author) {
            return null;
        }

        return [
            'id' => (int) $talk->author->id,
            'name' => (string) $talk->author->name,
            'nickname' => $talk->author->nickname ? (string) $talk->author->nickname : null,
            'email' => (string) $talk->author->email,
        ];
    }

    private static function category(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('categories')) {
            return null;
        }

        $category = $talk->categories
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

    private static function images(Talk $talk): array
    {
        if (! $talk->relationLoaded('images')) {
            return [];
        }

        return $talk->images
            ->map(fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => $media->publicPath(),
                'mime_type' => (string) $media->mime_type,
                'size' => (int) $media->size,
                'width' => $media->width !== null ? (int) $media->width : null,
                'height' => $media->height !== null ? (int) $media->height : null,
                'sort_order' => (int) $media->sort_order,
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    public static function operationHistory(OperationHistory $history): array
    {
        return OperationHistoryDto::fromModel($history)->toArray();
    }

    private static function poll(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('poll') || ! $talk->poll) {
            return null;
        }

        return [
            'id' => (int) $talk->poll->id,
            'allow_multiple' => (bool) $talk->poll->allow_multiple,
            'options' => $talk->poll->relationLoaded('options')
                ? $talk->poll->options
                    ->map(fn (TalkPollOption $option): array => [
                        'id' => (int) $option->id,
                        'content' => (string) $option->content,
                        'sort_order' => (int) $option->sort_order,
                        'vote_count' => (int) $option->vote_count,
                    ])
                    ->values()
                    ->all()
                : [],
            'created_at' => $talk->poll->created_at?->toISOString(),
            'updated_at' => $talk->poll->updated_at?->toISOString(),
        ];
    }

    public static function comment(TalkComment $comment): array
    {
        return [
            'id' => (int) $comment->id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'is_reply' => $comment->isReply(),
            'author' => self::commentAuthor($comment),
            'content' => (string) $comment->content,
            'status' => (string) $comment->status,
            'author_ip' => $comment->author_ip,
            'like_count' => (int) $comment->like_count,
            'mention' => self::commentMention($comment),
            'report' => self::commentReport($comment),
            'operation_histories' => self::commentOperationHistories($comment),
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
            'deleted_at' => $comment->deleted_at?->toISOString(),
        ];
    }

    private static function report(Talk $talk): ?array
    {
        if (! $talk->relationLoaded('contentReportState') || ! $talk->contentReportState) {
            return null;
        }

        return self::reportState($talk->contentReportState);
    }

    private static function commentReport(TalkComment $comment): ?array
    {
        if (! $comment->relationLoaded('contentReportState') || ! $comment->contentReportState) {
            return null;
        }

        return self::reportState($comment->contentReportState);
    }

    private static function reportState(ContentReportState $state): ?array
    {
        if ((string) $state->report_status === ContentReportState::STATUS_NONE) {
            return null;
        }

        return [
            'status' => (string) $state->report_status,
            'label' => $state->statusLabel(),
        ];
    }

    private static function commentAuthor(TalkComment $comment): ?array
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

    private static function commentOperationHistories(TalkComment $comment): array
    {
        if (! $comment->relationLoaded('operationHistories')) {
            return [];
        }

        return $comment->operationHistories
            ->map(fn (OperationHistory $history): array => self::commentOperationHistory($history))
            ->values()
            ->all();
    }

    private static function commentMention(TalkComment $comment): ?array
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
            'mentioned_by_user_id' => $mention->mentioned_by_user_id ? (int) $mention->mentioned_by_user_id : null,
            'mention_text' => $mention->mention_text,
            'mentioned_user_name' => $mention->relationLoaded('mentionedUser') && $mention->mentionedUser
                ? (string) $mention->mentionedUser->name
                : null,
        ];
    }

    private static function commentOperationHistory(OperationHistory $history): array
    {
        $change = $history->changes->first();
        $historyDto = OperationHistoryDto::fromModel($history);

        return [
            'actor_label' => $historyDto->actorLabel,
            'action' => $historyDto->action,
            'action_label' => $historyDto->actionLabel,
            'field' => $historyDto->field,
            'before_value' => $historyDto->beforeValue,
            'after_value' => $historyDto->afterValue,
            'changes' => $historyDto->changes,
            'status' => $change?->field_key === 'status' ? $change->after_value : null,
            'created_at' => $history->created_at?->toISOString(),
            'reason' => $history->reason,
        ];
    }
}
