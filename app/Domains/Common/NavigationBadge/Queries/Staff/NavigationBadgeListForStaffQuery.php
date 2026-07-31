<?php

declare(strict_types=1);

namespace App\Domains\Common\NavigationBadge\Queries\Staff;

use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEntry\Models\HospitalEntry;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use App\Domains\HospitalEventAd\Models\HospitalEventAd;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Builder;

final class NavigationBadgeListForStaffQuery
{
    /**
     * @param  list<string>  $paths
     * @return array<string, int>
     */
    public function counts(array $paths): array
    {
        $counts = array_fill_keys($paths, 0);

        foreach ($paths as $path) {
            $counts[$path] = match ($path) {
                '/hospital-manage/hospitals' => $this->countPendingAllowStatus(Hospital::query(), Hospital::ALLOW_PENDING),
                '/hospital-manage/doctors' => $this->countPendingAllowStatus(HospitalDoctor::query(), HospitalDoctor::ALLOW_PENDING),
                '/hospital-manage/hospital-entries' => $this->countPendingAllowStatus(HospitalEntry::query(), HospitalEntry::ALLOW_PENDING),
                '/customer-db-manage/events' => HospitalEventDB::query()
                    ->where('status', HospitalEventDB::STATUS_NEW)
                    ->count(),
                '/customer-db-manage/real-models' => HospitalEventRealModelDB::query()
                    ->where('status', HospitalEventRealModelDB::STATUS_RECEIVED)
                    ->count(),
                '/ads-manage/events' => $this->countPendingAllowStatus(HospitalEvent::query(), HospitalEvent::ALLOW_PENDING),
                '/ads-manage/event-ads' => $this->countPendingAllowStatus(HospitalEventAd::query(), HospitalEventAd::ALLOW_PENDING),
                default => $counts[$path],
            };
        }

        $this->applyReportedContentCounts($counts, $paths);

        return $counts;
    }

    private function countPendingAllowStatus(Builder $query, string $allowStatus): int
    {
        return $query
            ->where('allow_status', $allowStatus)
            ->count();
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<string>  $paths
     */
    private function applyReportedContentCounts(array &$counts, array $paths): void
    {
        $requestedPaths = array_fill_keys($paths, true);
        $targetTypesByPath = [
            '/reported-post-manage/talks' => [Talk::class, TalkComment::class],
            '/reported-post-manage/hospital-evaluations' => [HospitalEvaluation::class],
            '/reported-post-manage/chats' => [ChatMessage::class],
        ];
        $requestedTargetTypes = [];

        foreach ($targetTypesByPath as $path => $targetTypes) {
            if (! isset($requestedPaths[$path])) {
                continue;
            }

            array_push($requestedTargetTypes, ...$targetTypes);
        }

        if ($requestedTargetTypes !== []) {
            $countsByTargetType = ContentReportState::query()
                ->selectRaw('target_type, COUNT(*) AS aggregate')
                ->whereIn('target_type', $requestedTargetTypes)
                ->where('report_status', ContentReportState::STATUS_REPORTED)
                ->groupBy('target_type')
                ->pluck('aggregate', 'target_type');

            foreach ($targetTypesByPath as $path => $targetTypes) {
                if (! isset($requestedPaths[$path])) {
                    continue;
                }

                $counts[$path] = array_sum(array_map(
                    static fn (string $targetType): int => (int) ($countsByTargetType[$targetType] ?? 0),
                    $targetTypes,
                ));
            }
        }

        $reviewPaths = [
            HospitalReview::CATEGORY_DOMAIN_SURGERY => '/reported-post-manage/surgery-reviews',
            HospitalReview::CATEGORY_DOMAIN_TREATMENT => '/reported-post-manage/treatment-reviews',
        ];

        if (! array_intersect($paths, array_values($reviewPaths))) {
            return;
        }

        $reviewCounts = $this->reportedHospitalReviewCounts();
        $commentCounts = $this->reportedHospitalReviewCommentCounts();

        foreach ($reviewPaths as $categoryDomain => $path) {
            if (! isset($requestedPaths[$path])) {
                continue;
            }

            $counts[$path] = (int) ($reviewCounts[$categoryDomain] ?? 0)
                + (int) ($commentCounts[$categoryDomain] ?? 0);
        }
    }

    /**
     * @return array<string, int>
     */
    private function reportedHospitalReviewCounts(): array
    {
        return ContentReportState::query()
            ->join('hospital_reviews', 'hospital_reviews.id', '=', 'content_report_states.target_id')
            ->where('content_report_states.target_type', HospitalReview::class)
            ->where('content_report_states.report_status', ContentReportState::STATUS_REPORTED)
            ->whereNull('hospital_reviews.deleted_at')
            ->selectRaw('hospital_reviews.category_domain, COUNT(*) AS aggregate')
            ->groupBy('hospital_reviews.category_domain')
            ->pluck('aggregate', 'hospital_reviews.category_domain')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function reportedHospitalReviewCommentCounts(): array
    {
        return ContentReportState::query()
            ->join('hospital_review_comments', 'hospital_review_comments.id', '=', 'content_report_states.target_id')
            ->join('hospital_reviews', 'hospital_reviews.id', '=', 'hospital_review_comments.hospital_review_id')
            ->where('content_report_states.target_type', HospitalReviewComment::class)
            ->where('content_report_states.report_status', ContentReportState::STATUS_REPORTED)
            ->whereNull('hospital_review_comments.deleted_at')
            ->whereNull('hospital_reviews.deleted_at')
            ->selectRaw('hospital_reviews.category_domain, COUNT(*) AS aggregate')
            ->groupBy('hospital_reviews.category_domain')
            ->pluck('aggregate', 'hospital_reviews.category_domain')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }
}
