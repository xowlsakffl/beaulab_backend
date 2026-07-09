<?php

namespace App\Domains\Common\ContentReport\Queries\Staff;

use App\Common\Support\DateRangeFilter;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ReportedContentListForStaffQuery
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    public function paginate(string $targetClass, array $filters): LengthAwarePaginator
    {
        $builder = $this->baseBuilder($targetClass, $filters)
            ->with(['processedBy:id,name,email', 'warningProcessedBy:id,name,email']);

        $this->applyFilters($builder, $targetClass, $filters);

        $sort = in_array($filters['sort'] ?? null, [
            'target_id',
            'report_status',
            'report_count',
            'recent_hour_report_count',
            'first_reported_at',
            'last_reported_at',
            'updated_at',
        ], true) ? (string) $filters['sort'] : 'first_reported_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $builder
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15))
            ->withQueryString();
    }

    /**
     * @param  Collection<int, ContentReportState>  $states
     * @return array<string, array{latest_report: ?ContentReport, reason_counts: Collection<int, object>}>
     */
    public function reportSummaries(Collection $states): array
    {
        if ($states->isEmpty()) {
            return [];
        }

        $summaries = [];
        $states->each(function (ContentReportState $state) use (&$summaries): void {
            $summaries[$this->reportKey($state->target_type, (int) $state->target_id)] = [
                'latest_report' => null,
                'reason_counts' => collect(),
            ];
        });

        $latestReportQuery = ContentReport::query();
        $this->applyReportTargetFilter($latestReportQuery, $states);

        $latestReportIds = $latestReportQuery
            ->selectRaw('MAX(id) AS id')
            ->groupBy('target_type', 'target_id')
            ->pluck('id')
            ->filter()
            ->values();

        if ($latestReportIds->isNotEmpty()) {
            ContentReport::query()
                ->whereIn('id', $latestReportIds)
                ->with(['reporter:id,name,nickname,email', 'items'])
                ->get()
                ->each(function (ContentReport $report) use (&$summaries): void {
                    $key = $this->reportKey($report->target_type, (int) $report->target_id);
                    $summaries[$key]['latest_report'] = $report;
                });
        }

        $reasonCountQuery = ContentReport::query();
        $this->applyReportTargetFilter($reasonCountQuery, $states);

        $reasonCountQuery
            ->select('target_type', 'target_id', 'reason')
            ->selectRaw('COUNT(*) AS count')
            ->groupBy('target_type', 'target_id', 'reason')
            ->orderByDesc('count')
            ->get()
            ->groupBy(fn (object $row): string => $this->reportKey((string) $row->target_type, (int) $row->target_id))
            ->each(function (Collection $reasonCounts, string $key) use (&$summaries): void {
                $summaries[$key]['reason_counts'] = $reasonCounts->values();
            });

        return $summaries;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function baseBuilder(string $targetClass, array $filters): Builder
    {
        $builder = ContentReportState::query()
            ->where('target_type', $targetClass)
            ->where('report_status', '!=', ContentReportState::STATUS_NONE);

        if (! empty($filters['category_domain'])) {
            $this->applyCategoryDomainFilter($builder, $targetClass, (string) $filters['category_domain']);
        }

        return $builder;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyFilters(Builder $builder, string $targetClass, array $filters): void
    {
        if (! empty($filters['summary_filter'])) {
            $this->applySummaryFilter($builder, $targetClass, (string) $filters['summary_filter']);
        }

        $statuses = $filters['report_status'] ?? null;
        if (is_array($statuses) && $statuses !== []) {
            $builder->whereIn('report_status', $statuses);
        }

        if (! empty($filters['report_reason'])) {
            $builder->whereExists(function ($query) use ($filters): void {
                $query
                    ->selectRaw('1')
                    ->from('content_reports')
                    ->whereColumn('content_reports.target_type', 'content_report_states.target_type')
                    ->whereColumn('content_reports.target_id', 'content_report_states.target_id')
                    ->where('content_reports.reason', (string) $filters['report_reason']);
            });
        }

        if (! empty($filters['report_count_min'])) {
            $builder->where('report_count', '>=', (int) $filters['report_count_min']);
        }

        if (! empty($filters['report_count_max'])) {
            $builder->where('report_count', '<=', (int) $filters['report_count_max']);
        }

        if (! empty($filters['target_status'])) {
            $this->applyTargetStatusFilter($builder, $targetClass, (string) $filters['target_status']);
        }

        if (! empty($filters['warning_status'])) {
            $builder->where('warning_status', (string) $filters['warning_status']);
        }

        if (! empty($filters['target_author_id'])) {
            $this->applyTargetAuthorFilter($builder, $targetClass, (int) $filters['target_author_id']);
        }

        if (! empty($filters['start_date']) || ! empty($filters['end_date'])) {
            $this->applyDateFilter($builder, $targetClass, $filters);
        }

        if (! empty($filters['q'])) {
            $this->applySearchFilter(
                $builder,
                $targetClass,
                $filters['search_type'] ?? null,
                (string) $filters['q'],
            );
        }
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applySummaryFilter(Builder $builder, string $targetClass, string $summaryFilter): void
    {
        if ($summaryFilter === 'reported_or_auto_blocked') {
            $builder->whereIn('report_status', [
                ContentReportState::STATUS_REPORTED,
                ContentReportState::STATUS_AUTO_BLOCKED,
            ]);

            return;
        }

        if ($summaryFilter === 'today_report') {
            $todayStart = today();
            $tomorrowStart = $todayStart->copy()->addDay();

            $builder->whereExists(function ($query) use ($targetClass, $todayStart, $tomorrowStart): void {
                $query
                    ->selectRaw('1')
                    ->from('content_reports')
                    ->whereColumn('content_reports.target_type', 'content_report_states.target_type')
                    ->whereColumn('content_reports.target_id', 'content_report_states.target_id')
                    ->where('content_reports.target_type', $targetClass)
                    ->where('content_reports.created_at', '>=', $todayStart)
                    ->where('content_reports.created_at', '<', $tomorrowStart);
            });

            return;
        }

        if ($summaryFilter === 'recent_30_days_admin_hidden') {
            $builder
                ->where('report_status', ContentReportState::STATUS_ADMIN_HIDDEN)
                ->where('admin_hidden_at', '>=', now()->subDays(30));

            return;
        }

        if ($summaryFilter === 'recent_30_days_normal_visible') {
            $builder
                ->whereIn('report_status', [
                    ContentReportState::STATUS_NORMAL_VISIBLE,
                    ContentReportState::STATUS_REEXPOSED,
                ])
                ->where('normal_visible_at', '>=', now()->subDays(30));
        }
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

            if ($targetClass === HospitalVideo::class) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('author_id', $authorId);
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

            return;
        }
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyTargetStatusFilter(Builder $builder, string $targetClass, string $targetStatus): void
    {
        $builder->whereHasMorph('target', [$targetClass], function (Builder $query) use ($targetClass, $targetStatus): void {
            if ($targetClass === HospitalVideo::class) {
                $query->where('admin_status', $targetStatus);

                return;
            }

            $query->where('status', $targetStatus);
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyDateFilter(Builder $builder, string $targetClass, array $filters): void
    {
        $dateType = (string) ($filters['date_type'] ?? 'first_reported_at');

        if ($dateType === 'last_message_at' && $targetClass === ChatMessage::class) {
            $builder->whereHasMorph('target', [$targetClass], function (Builder $query) use ($filters): void {
                $query->whereHas('chat', function (Builder $chatQuery) use ($filters): void {
                    DateRangeFilter::apply($chatQuery, 'last_message_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
                });
            });

            return;
        }

        if ($dateType === 'created_at') {
            $builder->whereHasMorph('target', [$targetClass], function (Builder $query) use ($filters): void {
                DateRangeFilter::apply($query, 'created_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
            });

            return;
        }

        DateRangeFilter::apply($builder, 'first_reported_at', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applySearchFilter(Builder $builder, string $targetClass, mixed $searchType, string $q): void
    {
        $q = trim($q);

        if ($q === '') {
            return;
        }

        if (! is_string($searchType) || $searchType === '' || $searchType === 'all') {
            $this->applyComprehensiveSearchFilter($builder, $targetClass, $q);

            return;
        }

        match ($searchType) {
            'id' => $this->applyIdSearchFilter($builder, $targetClass, $q),
            'hospital_name' => $this->applyHospitalNameSearchFilter($builder, $targetClass, $q),
            'content' => $this->applyContentSearchFilter($builder, $targetClass, $q),
            default => $this->applyNicknameSearchFilter($builder, $targetClass, $q),
        };
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyComprehensiveSearchFilter(Builder $builder, string $targetClass, string $q): void
    {
        $builder->where(function (Builder $searchQuery) use ($targetClass, $q): void {
            if (ctype_digit($q)) {
                if ($targetClass === ChatMessage::class) {
                    $searchQuery->orWhereHasMorph('target', [$targetClass], fn (Builder $targetQuery) => $targetQuery
                        ->where('chat_id', (int) $q)
                        ->orWhereKey((int) $q));
                } else {
                    $searchQuery->orWhere('target_id', (int) $q);
                }
            }

            $searchQuery->orWhereHasMorph('target', [$targetClass], function (Builder $targetQuery) use ($targetClass, $q): void {
                $this->applyTargetComprehensiveTextSearch($targetQuery, $targetClass, $q);
            });
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyTargetComprehensiveTextSearch(Builder $targetQuery, string $targetClass, string $q): void
    {
        $targetQuery->where(function (Builder $textQuery) use ($targetClass, $q): void {
            if ($targetClass === Talk::class) {
                $textQuery
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery
                        ->where('nickname', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === TalkComment::class) {
                $textQuery
                    ->where('content', 'like', "%{$q}%")
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery
                        ->where('nickname', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"))
                    ->orWhereHas('talk', fn (Builder $talkQuery) => $talkQuery
                        ->where('title', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === HospitalReview::class) {
                $textQuery
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery
                        ->where('nickname', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"))
                    ->orWhereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                        ->where('name', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === HospitalReviewComment::class) {
                $textQuery
                    ->where('content', 'like', "%{$q}%")
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery
                        ->where('nickname', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"))
                    ->orWhereHas('review', fn (Builder $reviewQuery) => $reviewQuery
                        ->where('title', 'like', "%{$q}%")
                        ->orWhereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                            ->where('name', 'like', "%{$q}%")));

                return;
            }

            if ($targetClass === HospitalEvaluation::class) {
                $textQuery
                    ->where('content', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery
                        ->where('nickname', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"))
                    ->orWhereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                        ->where('name', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === ChatMessage::class) {
                $textQuery
                    ->where('body', 'like', "%{$q}%")
                    ->orWhereHas('sender', fn (Builder $senderQuery) => $senderQuery
                        ->where('nickname', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === HospitalVideo::class) {
                $textQuery
                    ->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                        ->where('name', 'like', "%{$q}%"));

                return;
            }

            $textQuery->whereRaw('1 = 0');
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyIdSearchFilter(Builder $builder, string $targetClass, string $q): void
    {
        if (! ctype_digit($q)) {
            $builder->whereRaw('1 = 0');

            return;
        }

        if ($targetClass === ChatMessage::class) {
            $builder->whereHasMorph('target', [$targetClass], fn (Builder $targetQuery) => $targetQuery
                ->where('chat_id', (int) $q));

            return;
        }

        $builder->where('target_id', (int) $q);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyNicknameSearchFilter(Builder $builder, string $targetClass, string $q): void
    {
        $builder->whereHasMorph('target', [$targetClass], function (Builder $targetQuery) use ($targetClass, $q): void {
            if (in_array($targetClass, [
                Talk::class,
                TalkComment::class,
                HospitalReview::class,
                HospitalReviewComment::class,
                HospitalEvaluation::class,
            ], true)) {
                $targetQuery->whereHas('author', fn (Builder $authorQuery) => $authorQuery
                    ->where('nickname', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === ChatMessage::class) {
                $targetQuery->whereHas('sender', fn (Builder $senderQuery) => $senderQuery
                    ->where('nickname', 'like', "%{$q}%"));

                return;
            }

            $targetQuery->whereRaw('1 = 0');
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyHospitalNameSearchFilter(Builder $builder, string $targetClass, string $q): void
    {
        $builder->whereHasMorph('target', [$targetClass], function (Builder $targetQuery) use ($targetClass, $q): void {
            if (in_array($targetClass, [HospitalReview::class, HospitalEvaluation::class, HospitalVideo::class], true)) {
                $targetQuery->whereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                    ->where('name', 'like', "%{$q}%"));

                return;
            }

            if ($targetClass === HospitalReviewComment::class) {
                $targetQuery->whereHas('review.hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                    ->where('name', 'like', "%{$q}%"));

                return;
            }

            $targetQuery->whereRaw('1 = 0');
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $targetClass
     */
    private function applyContentSearchFilter(Builder $builder, string $targetClass, string $q): void
    {
        $builder->whereHasMorph('target', [$targetClass], function (Builder $targetQuery) use ($targetClass, $q): void {
            if (in_array($targetClass, [Talk::class, HospitalReview::class], true)) {
                $targetQuery->where(function (Builder $textQuery) use ($q): void {
                    $textQuery
                        ->where('title', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });

                return;
            }

            if (in_array($targetClass, [
                TalkComment::class,
                HospitalReviewComment::class,
                HospitalEvaluation::class,
            ], true)) {
                $targetQuery->where('content', 'like', "%{$q}%");

                return;
            }

            if ($targetClass === ChatMessage::class) {
                $targetQuery->where('body', 'like', "%{$q}%");

                return;
            }

            if ($targetClass === HospitalVideo::class) {
                $targetQuery->where(function (Builder $textQuery) use ($q): void {
                    $textQuery
                        ->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });

                return;
            }

            $targetQuery->whereRaw('1 = 0');
        });
    }

    /**
     * @param  Collection<int, ContentReportState>  $states
     */
    private function applyReportTargetFilter(Builder $builder, Collection $states): void
    {
        $builder->where(function (Builder $query) use ($states): void {
            $states
                ->groupBy('target_type')
                ->each(function (Collection $typedStates, string $targetType) use ($query): void {
                    $targetIds = $typedStates
                        ->pluck('target_id')
                        ->map(static fn ($targetId): int => (int) $targetId)
                        ->unique()
                        ->values();

                    $query->orWhere(fn (Builder $typedQuery) => $typedQuery
                        ->where('target_type', $targetType)
                        ->whereIn('target_id', $targetIds));
                });
        });
    }

    private function reportKey(string $targetType, int $targetId): string
    {
        return "{$targetType}:{$targetId}";
    }
}
