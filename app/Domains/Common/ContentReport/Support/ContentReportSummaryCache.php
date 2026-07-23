<?php

namespace App\Domains\Common\ContentReport\Support;

use App\Domains\Common\Cache\Support\StaffSummaryCache;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Database\Eloquent\Model;

final class ContentReportSummaryCache
{
    /**
     * @param  class-string<Model>  $targetClass
     * @return array<string, int>
     */
    public static function remember(string $targetClass, ?string $categoryDomain, callable $resolver): array
    {
        /** @var array<string, int> $summary */
        $summary = StaffSummaryCache::remember(
            StaffSummaryCache::DOMAIN_CONTENT_REPORT,
            $resolver,
            self::parts($targetClass, $categoryDomain),
        );

        return $summary;
    }

    /**
     * @param  class-string<Model>  $targetClass
     */
    public static function forget(string $targetClass, ?string $categoryDomain = null): void
    {
        StaffSummaryCache::forget(
            StaffSummaryCache::DOMAIN_CONTENT_REPORT,
            self::parts($targetClass, null),
        );

        if ($categoryDomain !== null && $categoryDomain !== '') {
            StaffSummaryCache::forget(
                StaffSummaryCache::DOMAIN_CONTENT_REPORT,
                self::parts($targetClass, $categoryDomain),
            );
        }
    }

    public static function forgetForTarget(Model $target): void
    {
        self::forget($target::class, self::categoryDomainForTarget($target));

        if ($target instanceof HospitalVideo) {
            StaffSummaryCache::forget(StaffSummaryCache::DOMAIN_HOSPITAL_VIDEO);
        }
    }

    private static function categoryDomainForTarget(Model $target): ?string
    {
        if ($target instanceof HospitalReview || $target instanceof HospitalEvaluation) {
            return self::normalizeDomain($target->getAttribute('category_domain'));
        }

        if ($target instanceof HospitalReviewComment) {
            $target->loadMissing('review:id,category_domain');

            return self::normalizeDomain($target->review?->category_domain);
        }

        return null;
    }

    /**
     * @param  class-string<Model>  $targetClass
     * @return array<int, string>
     */
    private static function parts(string $targetClass, ?string $categoryDomain): array
    {
        $domain = $categoryDomain === null || $categoryDomain === '' ? 'all' : $categoryDomain;

        return [$targetClass, $domain, today()->toDateString()];
    }

    private static function normalizeDomain(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
