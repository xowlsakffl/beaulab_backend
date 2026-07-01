<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Dto\Staff\ContentReportStateForStaffDto;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Queries\Staff\ReportedContentDetailForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalEvaluation\Dto\Staff\HospitalEvaluationForStaffDto;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Dto\Staff\HospitalReviewCommentForStaffDto;
use App\Domains\HospitalReview\Dto\Staff\HospitalReviewForStaffDetailDto;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Dto\Staff\TalkCommentForStaffDto;
use App\Domains\Talk\Dto\Staff\TalkForStaffDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class ReportedContentDetailForStaffAction
{
    public function __construct(
        private readonly ReportedContentDetailForStaffQuery $query,
    ) {}

    public function execute(string $targetAlias, int $targetId, bool $includeTarget = true): array
    {
        $targetClass = ContentReportTargetRegistry::classForAlias($targetAlias);

        if ($targetClass === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        Gate::authorize('viewAny', [ContentReportState::class, $targetAlias]);

        $target = ContentReportTargetRegistry::resolveTarget($targetAlias, $targetId);
        $target->loadMissing($includeTarget
            ? $this->targetLoadRelations($target)
            : $this->targetAuthorLoadRelations($target));

        $state = $this->query->state($targetClass, $targetId);
        $latestReport = $this->query->latestReport($targetClass, $targetId);
        $this->loadLatestReportItemTargets($latestReport);
        $reasonCounts = $this->query->reasonCounts($targetClass, $targetId);

        return [
            'target_type' => $targetAlias,
            'target_id' => $targetId,
            ...($includeTarget ? [
                'target' => $this->targetToArray($target),
            ] : []),
            'author' => $this->targetAuthor($target),
            ...($target instanceof ChatMessage ? [
                'operation_histories' => $this->operationHistories($target),
            ] : []),
            'report' => ContentReportStateForStaffDto::fromModel(
                $state,
                $latestReport,
                $reasonCounts,
                $target instanceof ChatMessage,
            )->toArray(),
        ];
    }

    /**
     * @return array<class-string<Model>, array<int, string>>
     */
    private function targetRelations(): array
    {
        return [
            Talk::class => [
                'categories',
            ],
            TalkComment::class => [
                'talk.categories',
                'mentions.mentionedUser',
            ],
            HospitalReview::class => [
                'hospital.businessRegistration',
                'doctor',
                'beforeImages',
                'afterImages',
                'categories',
            ],
            HospitalReviewComment::class => [
                'review.beforeImages',
                'review.afterImages',
                'review.categories',
            ],
            HospitalEvaluation::class => [
                'hospital.businessRegistration',
                'doctor',
                'categories',
            ],
            ChatMessage::class => [
                'chat:id,last_message_at',
                'sender:id,name,nickname,email,phone,warning_count,created_at',
                'attachments',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function targetLoadRelations(Model $target): array
    {
        $relations = $this->targetRelations()[$target::class] ?? [];

        if (! $target instanceof ChatMessage) {
            array_unshift($relations, 'author:id,name,nickname,email,phone,warning_count,created_at');
        }

        return $relations;
    }

    /**
     * @return array<int, string>
     */
    private function targetAuthorLoadRelations(Model $target): array
    {
        return $target instanceof ChatMessage
            ? ['sender:id,name,nickname,email,phone,warning_count,created_at']
            : ['author:id,name,nickname,email,phone,warning_count,created_at'];
    }

    private function targetToArray(Model $target): ?array
    {
        return match (true) {
            $target instanceof Talk => TalkForStaffDto::fromModel($target)->toArray(),
            $target instanceof TalkComment => [
                ...TalkCommentForStaffDto::fromModel($target)->toArray(),
                'author_ip' => $target->author_ip,
            ],
            $target instanceof HospitalReview => HospitalReviewForStaffDetailDto::fromModel($target)->toArray(),
            $target instanceof HospitalReviewComment => [
                ...HospitalReviewCommentForStaffDto::fromModel($target)->toArray(),
                'author_ip' => $target->author_ip,
            ],
            $target instanceof HospitalEvaluation => HospitalEvaluationForStaffDto::fromModel($target)->toArray(),
            $target instanceof ChatMessage => $this->chatMessageToArray($target),
            default => null,
        };
    }

    private function loadLatestReportItemTargets(?ContentReport $latestReport): void
    {
        if (! $latestReport instanceof ContentReport || ! $latestReport->relationLoaded('items')) {
            return;
        }

        $latestReport->items->loadMorph('target', [
            ChatMessage::class => [
                'sender:id,name,nickname,email',
                'attachments',
            ],
        ]);
    }

    private function chatMessageToArray(ChatMessage $message): array
    {
        $chat = $message->relationLoaded('chat') ? $message->chat : null;

        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->chat_id,
            'created_at' => $message->created_at?->toISOString() ?? '',
            'last_message_at' => $chat?->last_message_at?->toISOString(),
            'author_ip' => null,
            'sender' => $this->sender($message),
            'body' => $message->body,
            'body_preview' => $this->contentPreview($message->body),
            'message_type' => (string) $message->message_type,
            'attachments' => $this->attachments($message),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attachments(ChatMessage $message): array
    {
        if (! $message->relationLoaded('attachments')) {
            return [];
        }

        return $message->attachments
            ->map(static fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => (string) $media->path,
                'url' => Storage::disk((string) $media->disk)->url((string) $media->path),
                'mime_type' => $media->mime_type,
                'size' => $media->size !== null ? (int) $media->size : null,
                'width' => $media->width !== null ? (int) $media->width : null,
                'height' => $media->height !== null ? (int) $media->height : null,
                'sort_order' => (int) $media->sort_order,
                'metadata' => $media->metadata,
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function sender(ChatMessage $message, bool $includeDetail = false): ?array
    {
        if (! $message->relationLoaded('sender') || ! $message->getRelation('sender')) {
            return null;
        }

        return $this->userToArray($message->getRelation('sender'), $includeDetail);
    }

    private function targetAuthor(Model $target): ?array
    {
        if ($target instanceof ChatMessage) {
            return $this->sender($target, true);
        }

        if (! $target->relationLoaded('author') || ! $target->getRelation('author')) {
            return null;
        }

        return $this->userToArray($target->getRelation('author'), true);
    }

    private function userToArray(Model $user, bool $includeDetail = false): array
    {
        $attributes = $user->getAttributes();

        $payload = [
            'id' => (int) $user->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];

        if (! $includeDetail) {
            return $payload;
        }

        return [
            ...$payload,
            'phone' => isset($attributes['phone']) && trim((string) $attributes['phone']) !== ''
                ? (string) $attributes['phone']
                : null,
            'warning_count' => (int) ($attributes['warning_count'] ?? 0),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    private function operationHistories(Model $target): array
    {
        return OperationHistory::query()
            ->where('target_type', $target::class)
            ->where('target_id', (int) $target->getKey())
            ->with('actor')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (OperationHistory $history): array => OperationHistoryDto::fromModel($history)->toArray())
            ->values()
            ->all();
    }

    private function contentPreview(mixed $value): ?string
    {
        $content = trim((string) $value);

        if ($content === '') {
            return null;
        }

        return mb_strlen($content) > 120 ? mb_substr($content, 0, 120).'...' : $content;
    }
}
