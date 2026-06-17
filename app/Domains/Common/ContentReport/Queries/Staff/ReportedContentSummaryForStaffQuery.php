<?php

declare(strict_types=1);

namespace App\Domains\Common\ContentReport\Queries\Staff;

use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportSummaryCache;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
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
    private function uncachedSummary(string $targetClass, ?string $categoryDomain): array
    {
        $builder = $this->baseBuilder($targetClass, $categoryDomain);
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
    private function baseBuilder(string $targetClass, ?string $categoryDomain): Builder
    {
        $builder = ContentReportState::query()
            ->where('target_type', $targetClass)
            ->where('report_status', '!=', ContentReportState::STATUS_NONE);

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
