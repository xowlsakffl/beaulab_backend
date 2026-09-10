<?php

namespace App\Domains\Common\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CategoryUsage extends Model
{
    public const USAGE_HOSPITAL_DOCTOR_SUBJECT = 'HOSPITAL_DOCTOR_SUBJECT';

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
        'sort_order',
        'status',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    public static function constrainActiveCategoryExists($query, string $usage): void
    {
        $query
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereExists(static function ($usageQuery) use ($usage): void {
                $usageQuery
                    ->selectRaw('1')
                    ->from('category_usages')
                    ->whereColumn('category_usages.category_id', 'categories.id')
                    ->where('category_usages.usage', $usage)
                    ->where('category_usages.status', self::STATUS_ACTIVE);
            });
    }

    /**
     * @param  array<int, string>  $usages
     */
    public static function constrainActiveCategoryExistsAny($query, array $usages): void
    {
        $query
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereExists(static function ($usageQuery) use ($usages): void {
                $usageQuery
                    ->selectRaw('1')
                    ->from('category_usages')
                    ->whereColumn('category_usages.category_id', 'categories.id')
                    ->whereIn('category_usages.usage', array_values($usages))
                    ->where('category_usages.status', self::STATUS_ACTIVE);
            });
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

        $rows = Category::query()
            ->join('category_usages', 'category_usages.category_id', '=', 'categories.id')
            ->where('categories.domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->where('categories.status', Category::STATUS_ACTIVE)
            ->where('category_usages.status', self::STATUS_ACTIVE)
            ->whereIn('category_usages.usage', array_values($usages))
            ->orderBy('category_usages.sort_order')
            ->orderBy('categories.id')
            ->get([
                'category_usages.usage',
                'categories.full_path',
            ]);

        $pathsByUsage = [];
        foreach ($rows as $row) {
            $usage = (string) $row->usage;
            $fullPath = trim((string) $row->full_path);
            if ($fullPath === '') {
                continue;
            }

            $pathsByUsage[$usage][] = $fullPath;
        }

        return $pathsByUsage;
    }
}
