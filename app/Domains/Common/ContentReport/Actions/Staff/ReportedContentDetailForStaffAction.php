<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Dto\Staff\ContentReportStateForStaffDto;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Queries\Staff\ReportedContentDetailForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
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

final class ReportedContentDetailForStaffAction
{
    public function __construct(
        private readonly ReportedContentDetailForStaffQuery $query,
    ) {}

    public function execute(string $targetAlias, int $targetId): array
    {
        $targetClass = ContentReportTargetRegistry::classForAlias($targetAlias);

        if ($targetClass === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        Gate::authorize('viewAny', $targetClass);

        $target = ContentReportTargetRegistry::resolveTarget($targetAlias, $targetId);
        $target->loadMissing($this->targetLoadRelations($target));

        $state = $this->query->state($targetClass, $targetId);
        $latestReport = $this->query->latestReport($targetClass, $targetId);
        $this->loadLatestReportItemTargets($latestReport);
        $reasonCounts = $this->query->reasonCounts($targetClass, $targetId);

        return [
            'target_type' => $targetAlias,
            'target_id' => $targetId,
            'target' => $this->targetToArray($target),
            ...($target instanceof ChatMessage ? ['author' => $this->sender($target, true)] : []),
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
        ];
    }

    private function sender(ChatMessage $message, bool $includeDetail = false): ?array
    {
        if (! $message->relationLoaded('sender') || ! $message->getRelation('sender')) {
            return null;
        }

        return $this->userToArray($message->getRelation('sender'), $includeDetail);
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

    private function contentPreview(mixed $value): ?string
    {
        $content = trim((string) $value);

        if ($content === '') {
            return null;
        }

        return mb_strlen($content) > 120 ? mb_substr($content, 0, 120).'...' : $content;
    }
}
