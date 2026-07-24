<?php

namespace App\Domains\Common\Category\Definitions;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use InvalidArgumentException;
use RuntimeException;

final class CategoryDefinitions
{
    private const TREES = [
        Category::DOMAIN_HOSPITAL_MEDICAL => 'hospital_medical',
        Category::DOMAIN_HOSPITAL_EVALUATION => 'hospital_evaluation',
        Category::DOMAIN_BEAUTY => 'beauty',
        Category::DOMAIN_TALK => 'talk',
        Category::DOMAIN_FAQ => 'faq',
    ];

    private const HOSPITAL_MEDICAL_USAGES = [
        CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT => 'hospital_doctor_subject',
        CategoryUsage::USAGE_HOSPITAL_REVIEW_SURGERY => 'hospital_review_surgery',
        CategoryUsage::USAGE_HOSPITAL_REVIEW_TREATMENT => 'hospital_review_treatment',
        CategoryUsage::USAGE_HOSPITAL_EVENT_SURGERY => 'hospital_event_surgery',
        CategoryUsage::USAGE_HOSPITAL_EVENT_TREATMENT => 'hospital_event_treatment',
        CategoryUsage::USAGE_HOSPITAL_VIDEO_CATEGORY => 'hospital_video_category',
        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_SURGERY => 'hospital_event_ad_surgery',
        CategoryUsage::USAGE_HOSPITAL_EVENT_AD_TREATMENT => 'hospital_event_ad_treatment',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function tree(string $domain): array
    {
        $name = self::TREES[$domain] ?? null;
        if ($name === null) {
            throw new InvalidArgumentException("Unknown category domain: {$domain}");
        }

        return self::loadArray("trees/{$name}.php", 'Category tree');
    }

    /**
     * @return array<string, string>
     */
    public static function hospitalMedicalUsages(): array
    {
        return self::HOSPITAL_MEDICAL_USAGES;
    }

    /**
     * @return array<int, array{code:string, sort_order?:int}>
     */
    public static function usageItems(string $usage): array
    {
        $name = self::HOSPITAL_MEDICAL_USAGES[$usage] ?? null;
        if ($name === null) {
            throw new InvalidArgumentException("Unknown category usage: {$usage}");
        }

        return self::loadArray("usages/{$name}.php", 'Category usage');
    }

    /**
     * @return array<int, mixed>
     */
    private static function loadArray(string $relativePath, string $label): array
    {
        $path = __DIR__."/data/{$relativePath}";
        if (! is_file($path)) {
            throw new RuntimeException("{$label} definition file does not exist: {$path}");
        }

        $data = require $path;

        if (! is_array($data)) {
            throw new RuntimeException("{$label} definition must return an array: {$path}");
        }

        return $data;
    }
}
