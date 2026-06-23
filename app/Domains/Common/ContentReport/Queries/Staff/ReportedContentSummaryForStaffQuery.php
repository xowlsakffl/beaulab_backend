<?php

declare(strict_types=1);

namespace App\Domains\Common\ContentReport\Queries\Staff;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportSummaryCache;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Builder;

final class ReportedContentSummaryForStaffQuery
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     * @return array<string, int>
     */
    public function get(string $targetClass, array $filters): array
    {
        $categoryDomain = $this->summaryCategoryDomain($filters);
        $targetAuthorId = $this->summaryTargetAuthorId($filters);

        if ($targetAuthorId !== null) {
            return $this->uncachedSummary($targetClass, $categoryDomain, $targetAuthorId);
        }

        return ContentReportSummaryCache::remember(
            $targetClass,
            $categoryDomain,
            fn (): array => $this->uncachedSummary($targetClass, $categoryDomain),
        );
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     * @return array<string, int>
     */
    private function uncachedSummary(string $targetClass, ?string $categoryDomain, ?int $targetAuthorId = null): array
    {
        $builder = $this->baseBuilder($targetClass, $categoryDomain, $targetAuthorId);
        $todayStart = today();
        $tomorrowStart = $todayStart->copy()->addDay();

        return [
            'reported_or_auto_blocked_count' => (clone $builder)
                ->whereIn('report_status', [
                    ContentReportState::STATUS_REPORTED,
                    ContentReportState::STATUS_AUTO_BLOCKED,
                ])
                ->count(),
            'today_report_count' => ContentReport::query()
                ->where('target_type', $targetClass)
                ->where('created_at', '>=', $todayStart)
                ->where('created_at', '<', $tomorrowStart)
                ->when($targetAuthorId !== null, fn (Builder $query) => $query
                    ->where('target_author_id', $targetAuthorId))
                ->when($categoryDomain !== null, fn (Builder $query) => $this->applyCategoryDomainFilter(
                    $query,
                    $targetClass,
                    $categoryDomain,
                ))
                ->count(),
            'recent_30_days_admin_hidden_count' => (clone $builder)
                ->where('report_status', ContentReportState::STATUS_ADMIN_HIDDEN)
                ->where('admin_hidden_at', '>=', now()->subDays(30))
                ->count(),
            'recent_30_days_normal_visible_count' => (clone $builder)
                ->whereIn('report_status', [
                    ContentReportState::STATUS_NORMAL_VISIBLE,
                    ContentReportState::STATUS_REEXPOSED,
                ])
                ->where('normal_visible_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

     /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function baseBuilder(string $targetClass, ?string $categoryDomain, ?int $targetAuthorId = null): Builder
    {
        $builder = ContentReportState::query()
            ->where('target_type', $targetClass)
            ->where('report_status', '!=', ContentReportState::STATUS_NONE);

        if ($targetAuthorId !== null) {
            $this->applyTargetAuthorFilter($builder, $targetClass, $targetAuthorId);
        }

        if ($categoryDomain !== null) {
            $this->applyCategoryDomainFilter($builder, $targetClass, $categoryDomain);
        }

        return $builder;
    }

    private function summaryCategoryDomain(array $filters): ?string
    {
        $categoryDomain = $filters['category_domain'] ?? null;

        if (! is_string($categoryDomain) || $categoryDomain === '') {
            return null;
        }

        return $categoryDomain;
    }

    private function summaryTargetAuthorId(array $filters): ?int
    {
        $targetAuthorId = (int) ($filters['target_author_id'] ?? 0);

        return $targetAuthorId > 0 ? $targetAuthorId : null;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyTargetAuthorFilter(Builder $builder, string $targetClass, int $authorId): void
    {
        $builder->whereHasMorph('target', [$targetClass], function (Builder $query) use ($targetClass, $authorId): void {
            if ($targetClass === ChatMessage::class) {
                $query->where('sender_user_id', $authorId);

                return;
            }

            if (in_array($targetClass, [
                HospitalReview::class,
                HospitalReviewComment::class,
                HospitalEvaluation::class,
                Talk::class,
                TalkComment::class,
            ], true)) {
                $query->where('author_id', $authorId);

                return;
            }

            $query->whereRaw('1 = 0');
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyCategoryDomainFilter(Builder $builder, string $targetClass, string $categoryDomain): void
    {
        if ($targetClass === HospitalReview::class) {
            $builder->whereHasMorph('target', [$targetClass], fn (Builder $query) => $query
                ->where('category_domain', $categoryDomain));

            return;
        }

        if ($targetClass === HospitalReviewComment::class) {
            $builder->whereHasMorph('target', [$targetClass], fn (Builder $query) => $query
                ->whereHas('review', fn (Builder $reviewQuery) => $reviewQuery
                    ->where('category_domain', $categoryDomain)));
        }
    }
}
