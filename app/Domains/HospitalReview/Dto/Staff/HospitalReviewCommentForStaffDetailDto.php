<?php

namespace App\Domains\HospitalReview\Dto\Staff;

use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Models\HospitalReviewCommentMention;

final readonly class HospitalReviewCommentForStaffDetailDto
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public bool $isReply,
        public ?array $author,
        public string $content,
        public string $status,
        public ?string $authorIp,
        public int $likeCount,
        public ?array $mention,
        public ?array $report,
        public array $operationHistories,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(HospitalReviewComment $comment): self
    {
        return new self(
            id: (int) $comment->id,
            parentId: $comment->parent_id ? (int) $comment->parent_id : null,
            isReply: $comment->isReply(),
            author: self::author($comment),
            content: (string) $comment->content,
            status: (string) $comment->status,
            authorIp: $comment->author_ip,
            likeCount: (int) $comment->like_count,
            mention: self::mention($comment),
            report: self::report($comment),
            operationHistories: self::operationHistories($comment),
            createdAt: $comment->created_at?->toISOString(),
            updatedAt: $comment->updated_at?->toISOString(),
            deletedAt: $comment->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'is_reply' => $this->isReply,
            'author' => $this->author,
            'content' => $this->content,
            'status' => $this->status,
            'author_ip' => $this->authorIp,
            'like_count' => $this->likeCount,
            'mention' => $this->mention,
            'report' => $this->report,
            'operation_histories' => $this->operationHistories,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }

    private static function report(HospitalReviewComment $comment): ?array
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
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function mention(HospitalReviewComment $comment): ?array
    {
        if (! $comment->relationLoaded('mentions')) {
            return null;
        }

        $mention = $comment->mentions->first();

        if (! $mention instanceof HospitalReviewCommentMention) {
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

    private static function operationHistories(HospitalReviewComment $comment): array
    {
        if (! $comment->relationLoaded('operationHistories')) {
            return [];
        }

        return $comment->operationHistories
            ->map(function (OperationHistory $history): array {
                $change = $history->changes->first();
                $historyDto = OperationHistoryDto::fromModel($history);

                return [
                    'actor_label' => $historyDto->actorLabel,
                    'status' => $change?->field_key === 'status' ? $change->after_value : null,
                    'created_at' => $history->created_at?->toISOString(),
                    'reason' => $history->reason,
                ];
            })
            ->values()
            ->all();
    }
}
