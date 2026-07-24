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
use Illuminate\Database\Eloquent\Model;

final class NavigationBadgeListForStaffQuery
{
    /**
     * @param  list<string>  $paths
     * @return array<string, int>
     */
    public function counts(array $paths): array
    {
        $counts = [];

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
                '/reported-post-manage/surgery-reviews' => $this->countReportedHospitalReviews(HospitalReview::CATEGORY_DOMAIN_SURGERY),
                '/reported-post-manage/treatment-reviews' => $this->countReportedHospitalReviews(HospitalReview::CATEGORY_DOMAIN_TREATMENT),
                '/reported-post-manage/talks' => $this->countReportedTargets([
                    Talk::class,
                    TalkComment::class,
                ]),
                '/reported-post-manage/hospital-evaluations' => $this->countReportedTargets([
                    HospitalEvaluation::class,
                ]),
                '/reported-post-manage/chats' => $this->countReportedTargets([
                    ChatMessage::class,
                ]),
                default => 0,
            };
        }

        return $counts;
    }

    private function countPendingAllowStatus(Builder $query, string $allowStatus): int
    {
        return $query
            ->where('allow_status', $allowStatus)
            ->count();
    }

    private function countReportedHospitalReviews(string $categoryDomain): int
    {
        return $this->countReportedTargets([
            HospitalReview::class,
            HospitalReviewComment::class,
        ], $categoryDomain);
    }

    /**
     * @param  list<class-string<Model>>  $targetClasses
     */
    private function countReportedTargets(array $targetClasses, ?string $categoryDomain = null): int
    {
        $total = 0;

        foreach ($targetClasses as $targetClass) {
            $query = ContentReportState::query()
                ->where('target_type', $targetClass)
                ->where('report_status', ContentReportState::STATUS_REPORTED);

            if ($categoryDomain !== null) {
                $this->applyHospitalReviewCategoryDomain($query, $targetClass, $categoryDomain);
            }

            $total += $query->count();
        }

        return $total;
    }

    /**
     * @param  class-string<Model>  $targetClass
     */
    private function applyHospitalReviewCategoryDomain(Builder $query, string $targetClass, string $categoryDomain): void
    {
        if ($targetClass === HospitalReview::class) {
            $query->whereHasMorph('target', [$targetClass], fn (Builder $targetQuery) => $targetQuery
                ->where('category_domain', $categoryDomain));

            return;
        }

        if ($targetClass === HospitalReviewComment::class) {
            $query->whereHasMorph('target', [$targetClass], fn (Builder $targetQuery) => $targetQuery
                ->whereHas('review', fn (Builder $reviewQuery) => $reviewQuery
                    ->where('category_domain', $categoryDomain)));
        }
    }
}
