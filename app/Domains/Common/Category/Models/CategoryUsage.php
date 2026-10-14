<?php

namespace App\Domains\Common\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CategoryUsage extends Model
{
    public const USAGE_HOSPITAL_DOCTOR_SUBJECT = 'HOSPITAL_DOCTOR_SUBJECT';

    public const USAGE_HOSPITAL_SUBJECT = 'HOSPITAL_SUBJECT';

    public const USAGE_DOCTOR_SUBJECT = 'DOCTOR_SUBJECT';

    public const USAGE_HOSPITAL_REVIEW_SURGERY = 'HOSPITAL_REVIEW_SURGERY';

    public const USAGE_HOSPITAL_REVIEW_TREATMENT = 'HOSPITAL_REVIEW_TREATMENT';

    public const USAGE_HOSPITAL_EVENT_SURGERY = 'HOSPITAL_EVENT_SURGERY';

    public const USAGE_HOSPITAL_EVENT_TREATMENT = 'HOSPITAL_EVENT_TREATMENT';

    public const USAGE_HOSPITAL_EVENT_PROMOTION = 'HOSPITAL_EVENT_PROMOTION';

    public const USAGE_HOSPITAL_VIDEO_CATEGORY = 'HOSPITAL_VIDEO_CATEGORY';

    public const USAGE_HOSPITAL_EVENT_AD_SURGERY = 'HOSPITAL_EVENT_AD_SURGERY';

    public const USAGE_HOSPITAL_EVENT_AD_TREATMENT = 'HOSPITAL_EVENT_AD_TREATMENT';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'category_usages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usage',
        'category_id',
        'display_name',
        'sort_order',
        'status',
        'is_menu_visible',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sort_order' => 'integer',
        'is_menu_visible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        self::saved(fn () => \App\Domains\Common\Category\Support\CategoryUsageAvailability::forgetRequestCache());
        self::deleted(fn () => \App\Domains\Common\Category\Support\CategoryUsageAvailability::forgetRequestCache());
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return list<string>
     */
    public static function usages(): array
    {
        return [
            self::USAGE_HOSPITAL_DOCTOR_SUBJECT,
            self::USAGE_HOSPITAL_SUBJECT,
            self::USAGE_DOCTOR_SUBJECT,
            self::USAGE_HOSPITAL_REVIEW_SURGERY,
            self::USAGE_HOSPITAL_REVIEW_TREATMENT,
            self::USAGE_HOSPITAL_EVENT_SURGERY,
            self::USAGE_HOSPITAL_EVENT_TREATMENT,
            self::USAGE_HOSPITAL_EVENT_PROMOTION,
            self::USAGE_HOSPITAL_VIDEO_CATEGORY,
            self::USAGE_HOSPITAL_EVENT_AD_SURGERY,
            self::USAGE_HOSPITAL_EVENT_AD_TREATMENT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function hospitalEventUsages(): array
    {
        return [
            self::USAGE_HOSPITAL_EVENT_SURGERY,
            self::USAGE_HOSPITAL_EVENT_TREATMENT,
            self::USAGE_HOSPITAL_EVENT_PROMOTION,
        ];
    }

    public static function domainFor(string $usage): string
    {
        return match ($usage) {
            self::USAGE_HOSPITAL_DOCTOR_SUBJECT, self::USAGE_HOSPITAL_SUBJECT => Category::DOMAIN_HOSPITAL_SUBJECT,
            self::USAGE_DOCTOR_SUBJECT => Category::DOMAIN_DOCTOR_SUBJECT,
            self::USAGE_HOSPITAL_REVIEW_SURGERY, self::USAGE_HOSPITAL_REVIEW_TREATMENT, 'HOSPITAL_REVIEW' => Category::DOMAIN_HOSPITAL_REVIEW,
            self::USAGE_HOSPITAL_VIDEO_CATEGORY => Category::DOMAIN_HOSPITAL_VIDEO,
            default => Category::DOMAIN_HOSPITAL_MEDICAL,
        };
    }

    public static function eventUsageForGroup(?string $group): ?string
    {
        return match ($group) {
            'SURGERY' => self::USAGE_HOSPITAL_EVENT_SURGERY,
            'TREATMENT' => self::USAGE_HOSPITAL_EVENT_TREATMENT,
            'PROMOTION' => self::USAGE_HOSPITAL_EVENT_PROMOTION,
            default => null,
        };
    }

    public static function constrainActiveCategoryExists($query, string $usage, array $existingIds = []): void
    {
        self::constrainActiveCategoryExistsAny($query, [$usage], $existingIds);
    }

    /**
     * @param  array<int, string>  $usages
     */
    public static function constrainActiveCategoryExistsAny($query, array $usages, array $existingIds = []): void
    {
        $ids = app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->idsForUsages($usages);
        $query->whereIn('categories.domain', array_unique(array_map(self::domainFor(...), $usages)))
            ->whereIn('categories.id', array_values(array_unique([...$ids, ...$existingIds])));
    }

    /**
     * @param  array<int, string>  $usages
     * @return array<string, array<int, string>>
     */
    public static function activeCategoryFullPathsByUsage(array $usages): array
    {
        if ($usages === []) {
            return [];
        }

        $pathsByUsage = [];
        foreach ($usages as $usage) {
            $ids = app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->ids($usage);
            $pathsByUsage[$usage] = Category::whereIn('id', $ids)->whereNull('parent_id')->orderBy('sort_order')->pluck('full_path')->all();
        }

        return $pathsByUsage;
    }
}
