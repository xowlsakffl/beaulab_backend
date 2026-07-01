<?php

declare(strict_types=1);

namespace App\Domains\AccountUser\Queries\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;

final class AccountUserGetForStaffQuery
{
    /**
     * @return array<string, int>
     */
    public function consultationCounts(AccountUser $user): array
    {
        $userId = (int) $user->id;

        return [
            'event_dbs' => HospitalEventDB::query()->where('account_user_id', $userId)->count(),
            'real_model_dbs' => HospitalEventRealModelDB::query()->where('account_user_id', $userId)->count(),
        ];
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function activityCounts(AccountUser $user): array
    {
        $userId = (int) $user->id;
        $hospitalReviewCount = HospitalReview::query()
            ->where('author_id', $userId)
            ->where('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
            ->count();
        $hospitalReviewCommentCount = HospitalReviewComment::query()
            ->where('author_id', $userId)
            ->whereHas(
                'review',
                fn ($query) => $query->where('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
            )
            ->count();
        $treatmentReviewCount = HospitalReview::query()
            ->where('author_id', $userId)
            ->where('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
            ->count();
        $treatmentReviewCommentCount = HospitalReviewComment::query()
            ->where('author_id', $userId)
            ->whereHas(
                'review',
                fn ($query) => $query->where('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
            )
            ->count();
        $talkCount = Talk::query()->where('author_id', $userId)->count();
        $talkCommentCount = TalkComment::query()->where('author_id', $userId)->count();
        $hospitalEvaluationCount = HospitalEvaluation::query()->where('author_id', $userId)->count();

        return [
            'hospital_reviews' => [
                'posts' => $hospitalReviewCount,
                'comments' => $hospitalReviewCommentCount,
                'total' => $hospitalReviewCount + $hospitalReviewCommentCount,
            ],
            'treatment_reviews' => [
                'posts' => $treatmentReviewCount,
                'comments' => $treatmentReviewCommentCount,
                'total' => $treatmentReviewCount + $treatmentReviewCommentCount,
            ],
            'talks' => [
                'posts' => $talkCount,
                'comments' => $talkCommentCount,
                'total' => $talkCount + $talkCommentCount,
            ],
            'hospital_evaluations' => [
                'posts' => $hospitalEvaluationCount,
                'comments' => 0,
                'total' => $hospitalEvaluationCount,
            ],
        ];
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function reportedCounts(AccountUser $user): array
    {
        $userId = (int) $user->id;
        $hospitalReviewCount = $this->lockedReportCount(
            HospitalReview::class,
            HospitalReview::query()
                ->select('id')
                ->where('author_id', $userId)
                ->where('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY),
        );
        $hospitalReviewCommentCount = $this->lockedReportCount(
            HospitalReviewComment::class,
            HospitalReviewComment::query()
                ->select('id')
                ->where('author_id', $userId)
                ->whereHas(
                    'review',
                    fn ($query) => $query->where('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
                ),
        );
        $treatmentReviewCount = $this->lockedReportCount(
            HospitalReview::class,
            HospitalReview::query()
                ->select('id')
                ->where('author_id', $userId)
                ->where('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT),
        );
        $treatmentReviewCommentCount = $this->lockedReportCount(
            HospitalReviewComment::class,
            HospitalReviewComment::query()
                ->select('id')
                ->where('author_id', $userId)
                ->whereHas(
                    'review',
                    fn ($query) => $query->where('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
                ),
        );
        $talkCount = $this->lockedReportCount(
            Talk::class,
            Talk::query()->select('id')->where('author_id', $userId),
        );
        $talkCommentCount = $this->lockedReportCount(
            TalkComment::class,
            TalkComment::query()->select('id')->where('author_id', $userId),
        );
        $hospitalEvaluationCount = $this->lockedReportCount(
            HospitalEvaluation::class,
            HospitalEvaluation::query()->select('id')->where('author_id', $userId),
        );
        $warnedChatCount = ContentReportState::query()
            ->where('target_type', ChatMessage::class)
            ->where('warning_status', ContentReportState::WARNING_STATUS_WARNED)
            ->whereIn('target_id', ChatMessage::query()
                ->select('id')
                ->where('sender_user_id', $userId))
            ->count();

        return [
            'hospital_reviews' => [
                'posts' => $hospitalReviewCount,
                'comments' => $hospitalReviewCommentCount,
                'total' => $hospitalReviewCount + $hospitalReviewCommentCount,
            ],
            'treatment_reviews' => [
                'posts' => $treatmentReviewCount,
                'comments' => $treatmentReviewCommentCount,
                'total' => $treatmentReviewCount + $treatmentReviewCommentCount,
            ],
            'talks' => [
                'posts' => $talkCount,
                'comments' => $talkCommentCount,
                'total' => $talkCount + $talkCommentCount,
            ],
            'hospital_evaluations' => [
                'posts' => $hospitalEvaluationCount,
                'comments' => 0,
                'total' => $hospitalEvaluationCount,
            ],
            'chats' => [
                'warned' => $warnedChatCount,
                'total' => $warnedChatCount,
            ],
            'warnings' => [
                'count' => (int) $user->warning_count,
            ],
        ];
    }

    /**
     * @return list<array{ip: ?string, accessed_at: ?string}>
     */
    public function recentAccessLogs(AccountUser $user): array
    {
        return $user->accessLogs()
            ->select(['id', 'account_user_id', 'ip', 'accessed_at'])
            ->latest('accessed_at')
            ->limit(5)
            ->get()
            ->map(static fn ($log): array => [
                'ip' => $log->ip,
                'accessed_at' => $log->accessed_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function lockedReportCount(string $targetClass, mixed $targetIdsQuery): int
    {
        return ContentReportState::query()
            ->where('target_type', $targetClass)
            ->whereIn('report_status', [
                ContentReportState::STATUS_AUTO_BLOCKED,
                ContentReportState::STATUS_ADMIN_HIDDEN,
            ])
            ->whereIn('target_id', $targetIdsQuery)
            ->count();
    }
}
