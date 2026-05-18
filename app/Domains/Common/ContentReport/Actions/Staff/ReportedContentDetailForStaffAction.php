<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\ContentReport\Dto\Staff\ContentReportStateForStaffDto;
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
        $authorId = (int) $target->getAttribute('author_id');

        if ($authorId <= 0) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '작성자 정보가 없는 신고 대상입니다.');
        }

        $target->loadMissing([
            'author:id,name,nickname,email,phone,warning_count,created_at',
            ...($this->targetRelations()[$target::class] ?? []),
        ]);

        $state = $this->query->state($targetClass, $targetId);
        $latestReport = $this->query->latestReport($targetClass, $targetId);
        $reasonCounts = $this->query->reasonCounts($targetClass, $targetId);

        return [
            'target_type' => $targetAlias,
            'target_id' => $targetId,
            'target' => $this->targetToArray($target),
            'author' => $this->author($target),
            'author_stats' => $this->query->authorStats($authorId),
            'report' => ContentReportStateForStaffDto::fromModel(
                $state,
                $latestReport,
                $reasonCounts,
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
        ];
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
            default => null,
        };
    }

    private function author(Model $target): ?array
    {
        if (! $target->relationLoaded('author') || ! $target->getRelation('author')) {
            return null;
        }

        $author = $target->getRelation('author');
        $attributes = $author->getAttributes();

        return [
            'id' => (int) $author->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
            'phone' => isset($attributes['phone']) && trim((string) $attributes['phone']) !== ''
                ? (string) $attributes['phone']
                : null,
            'warning_count' => (int) ($attributes['warning_count'] ?? 0),
            'created_at' => $author->created_at?->toISOString(),
        ];
    }
}
