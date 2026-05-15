<?php

namespace App\Domains\Common\ContentReport\Support;

use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class ContentReportSummaryCache
{
    private const int TTL_SECONDS = 300;

    /**
     * @param  class-string<Model>  $targetClass
     * @return array<string, int>
     */
    public static function remember(string $targetClass, ?string $categoryDomain, callable $resolver): array
    {
        /** @var array<string, int> $summary */
        $summary = Cache::remember(
            self::key($targetClass, $categoryDomain),
            self::TTL_SECONDS,
            $resolver,
        );

        return $summary;
    }

    /**
     * @param  class-string<Model>  $targetClass
     */
    public static function forget(string $targetClass, ?string $categoryDomain = null): void
    {
        Cache::forget(self::key($targetClass, null));

        if ($categoryDomain !== null && $categoryDomain !== '') {
            Cache::forget(self::key($targetClass, $categoryDomain));
        }
    }

    public static function forgetForTarget(Model $target): void
    {
        self::forget($target::class, self::categoryDomainForTarget($target));
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
     */
    private static function key(string $targetClass, ?string $categoryDomain): string
    {
        $domain = $categoryDomain === null || $categoryDomain === '' ? 'all' : $categoryDomain;

        return 'staff:content-report-summary:'.sha1($targetClass.'|'.$domain.'|'.today()->toDateString());
    }

    private static function normalizeDomain(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
