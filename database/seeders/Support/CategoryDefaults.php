<?php

namespace Database\Seeders\Support;

use App\Domains\Common\Category\Definitions\CategoryDefinitions;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** 기존 운영 설정은 보존하고 누락된 기본 분류만 추가한다. */
final class CategoryDefaults
{
    public static function seedHospitalDepartments(): void
    {
        DB::transaction(fn () => self::seedDomainTree(
            Category::DOMAIN_HOSPITAL_DEPARTMENT,
            CategoryDefinitions::tree(Category::DOMAIN_HOSPITAL_DEPARTMENT),
        ));
    }

    public static function seedHospitalCategories(): void
    {
        DB::transaction(function (): void {
            $tree = CategoryDefinitions::tree(Category::DOMAIN_HOSPITAL_MEDICAL);

            self::seedDomainTree(Category::DOMAIN_HOSPITAL_MEDICAL, $tree);

            foreach (array_keys(CategoryDefinitions::hospitalMedicalUsages()) as $usage) {
                self::seedCategoryUsage($usage, CategoryDefinitions::usageItems($usage));
            }
        });
    }

    public static function seedHospitalEvaluationCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(
                Category::DOMAIN_HOSPITAL_EVALUATION,
                CategoryDefinitions::tree(Category::DOMAIN_HOSPITAL_EVALUATION),
            );
        });
    }

    public static function seedBeautyCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(
                Category::DOMAIN_BEAUTY,
                CategoryDefinitions::tree(Category::DOMAIN_BEAUTY),
            );
        });
    }

    public static function seedTalkCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(
                Category::DOMAIN_TALK,
                CategoryDefinitions::tree(Category::DOMAIN_TALK),
            );
        });
    }

    public static function seedFaqCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(
                Category::DOMAIN_FAQ,
                CategoryDefinitions::tree(Category::DOMAIN_FAQ),
            );
        });
    }

    /**
     * @param  array<int, array{name:string, code:string, group_code?:string|null, children?:array<int, array{name:string, code:string, group_code?:string|null, children?:array<int, array{name:string, code:string, group_code?:string|null}>}>}>  $tree
     */
    private static function seedDomainTree(string $domain, array $tree): void
    {
        foreach ($tree as $index => $node) {
            self::createMissingNode(
                domain: $domain,
                node: $node,
                parent: null,
                depth: 1,
                sortOrder: $index + 1,
            );
        }

    }

    /**
     * @param  array{name:string, code:string, group_code?:string|null, children?:array<int, array{name:string, code:string, group_code?:string|null, children?:array<int, array{name:string, code:string, group_code?:string|null}>}>}  $node
     */
    private static function createMissingNode(string $domain, array $node, ?Category $parent, int $depth, int $sortOrder): Category
    {
        $name = $node['name'];
        $groupCode = self::resolveGroupCode($node, $parent);
        $parentPath = $parent
            ? trim((string) ($parent->full_path ?: $parent->name))
            : null;
        $path = $parentPath ? "{$parentPath} > {$name}" : $name;

        $category = Category::query()->firstOrCreate(
            [
                'domain' => $domain,
                'code' => $node['code'],
            ],
            [
                'parent_id' => $parent?->id,
                'depth' => $depth,
                'group_code' => $groupCode,
                'name' => $name,
                'full_path' => $path,
                'sort_order' => $sortOrder,
                'status' => Category::STATUS_ACTIVE,
                'is_menu_visible' => true,
            ],
        );

        $children = $node['children'] ?? [];
        foreach ($children as $childIndex => $childNode) {
            self::createMissingNode(
                domain: $domain,
                node: $childNode,
                parent: $category,
                depth: (int) $category->depth + 1,
                sortOrder: $childIndex + 1,
            );
        }

        return $category;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function resolveGroupCode(array $node, ?Category $parent): ?string
    {
        $rawGroupCode = $node['group_code'] ?? null;
        $groupCode = is_string($rawGroupCode) && trim($rawGroupCode) !== ''
            ? trim($rawGroupCode)
            : ($parent?->group_code ? (string) $parent->group_code : null);

        if ($groupCode !== null && ! in_array($groupCode, Category::groupCodes(), true)) {
            throw new RuntimeException("알 수 없는 카테고리 그룹 코드입니다: {$groupCode}");
        }

        return $groupCode;
    }

    /**
     * @param  array<int, array{code:string, sort_order?:int}>  $items
     */
    private static function seedCategoryUsage(string $usage, array $items): void
    {
        $codes = collect($items)
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        $categoriesByCode = Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        foreach ($items as $index => $item) {
            $category = $categoriesByCode->get($item['code']);

            if (! $category instanceof Category) {
                throw new RuntimeException("카테고리 사용처가 존재하지 않는 코드를 참조합니다: {$item['code']}");
            }

            CategoryUsage::query()->firstOrCreate(
                [
                    'usage' => $usage,
                    'category_id' => (int) $category->id,
                ],
                [
                    'sort_order' => (int) ($item['sort_order'] ?? $index + 1),
                    'status' => CategoryUsage::STATUS_ACTIVE,
                ],
            );
        }

    }
}
