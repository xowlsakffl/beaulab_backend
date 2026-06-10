<?php

namespace Database\Factories;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'domain' => Category::DOMAIN_HOSPITAL_MEDICAL,
            'parent_id' => null,
            'depth' => 1,
            'name' => $name,
            'code' => strtoupper($name),
            'full_path' => $name,
            'sort_order' => 1,
            'status' => Category::STATUS_ACTIVE,
            'is_menu_visible' => true,
        ];
    }

    public function faq(): static
    {
        return $this->state(fn (): array => [
            'domain' => Category::DOMAIN_FAQ,
        ]);
    }

    public static function seedHospitalCategories(): void
    {
        DB::transaction(function (): void {
            $tree = self::categoryTree('hospital_medical');

            self::seedDomainTree(Category::DOMAIN_HOSPITAL_MEDICAL, $tree);
            self::seedCategoryUsage(
                CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT,
                self::categoryUsage('hospital_doctor_subject'),
            );
            self::seedCategoryUsage(
                CategoryUsage::USAGE_HOSPITAL_REVIEW_SURGERY,
                self::categoryUsage('hospital_review_surgery'),
            );
            self::seedCategoryUsage(
                CategoryUsage::USAGE_HOSPITAL_REVIEW_TREATMENT,
                self::categoryUsage('hospital_review_treatment'),
            );
            self::pruneStaleDomainCategories(Category::DOMAIN_HOSPITAL_MEDICAL, self::categoryCodes($tree));
        });
    }

    public static function seedHospitalEvaluationCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_EVALUATION, self::categoryTree('hospital_evaluation'));
        });
    }

    public static function seedBeautyCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_BEAUTY, self::categoryTree('beauty'));
        });
    }

    public static function seedTalkCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_TALK, self::categoryTree('talk'));
        });
    }

    public static function seedFaqCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_FAQ, self::categoryTree('faq'));
        });
    }

    /**
     * @param  array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string}>}>}>  $tree
     */
    private static function seedDomainTree(string $domain, array $tree): void
    {
        foreach ($tree as $index => $node) {
            self::upsertNode(
                domain: $domain,
                node: $node,
                parent: null,
                depth: 1,
                sortOrder: $index + 1,
            );
        }

        $codes = self::categoryCodes($tree);
        if ($domain === Category::DOMAIN_HOSPITAL_MEDICAL && $codes !== []) {
            self::markStaleDomainCategoriesInactive($domain, $codes);
        }
    }

    /**
     * @param  array{name:string, code:string, children?:array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string}>}>}  $node
     */
    private static function upsertNode(string $domain, array $node, ?Category $parent, int $depth, int $sortOrder): Category
    {
        $name = $node['name'];
        $parentPath = $parent
            ? trim((string) ($parent->full_path ?: $parent->name))
            : null;
        $path = $parentPath ? "{$parentPath} > {$name}" : $name;

        $category = Category::query()->updateOrCreate(
            [
                'domain' => $domain,
                'code' => $node['code'],
            ],
            [
                'parent_id' => $parent?->id,
                'depth' => $depth,
                'name' => $name,
                'full_path' => $path,
                'sort_order' => $sortOrder,
                'status' => Category::STATUS_ACTIVE,
                'is_menu_visible' => true,
            ]
        );

        $children = $node['children'] ?? [];
        foreach ($children as $childIndex => $childNode) {
            self::upsertNode(
                domain: $domain,
                node: $childNode,
                parent: $category,
                depth: $depth + 1,
                sortOrder: $childIndex + 1,
            );
        }

        return $category;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     * @return array<int, string>
     */
    private static function categoryCodes(array $tree): array
    {
        $codes = [];
        foreach ($tree as $node) {
            $code = $node['code'] ?? null;
            if (is_string($code) && $code !== '') {
                $codes[] = $code;
            }

            $children = $node['children'] ?? [];
            if (is_array($children) && $children !== []) {
                array_push($codes, ...self::categoryCodes($children));
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categoryTree(string $name): array
    {
        $path = database_path("seeders/data/categories/trees/{$name}.php");
        $tree = require $path;

        if (! is_array($tree)) {
            throw new \RuntimeException("Category seed data must return an array: {$path}");
        }

        return $tree;
    }

    /**
     * @return array<int, array{code:string, sort_order?:int}>
     */
    private static function categoryUsage(string $name): array
    {
        $path = database_path("seeders/data/categories/usages/{$name}.php");
        $usage = require $path;

        if (! is_array($usage)) {
            throw new \RuntimeException("Category usage seed data must return an array: {$path}");
        }

        return $usage;
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

        $categoryIds = [];

        foreach ($items as $index => $item) {
            $category = $categoriesByCode->get($item['code']);

            if (! $category instanceof Category) {
                throw new \RuntimeException("Category usage references missing category code: {$item['code']}");
            }

            $categoryIds[] = (int) $category->id;

            CategoryUsage::query()->updateOrCreate(
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

        CategoryUsage::query()
            ->where('usage', $usage)
            ->whereNotIn('category_id', $categoryIds)
            ->delete();
    }

    /**
     * @param  array<int, string>  $codes
     */
    private static function markStaleDomainCategoriesInactive(string $domain, array $codes): void
    {
        Category::query()
            ->where('domain', $domain)
            ->whereNotIn('code', $codes)
            ->update(['status' => Category::STATUS_INACTIVE]);
    }

    /**
     * @param  array<int, string>  $codes
     */
    private static function pruneStaleDomainCategories(string $domain, array $codes): void
    {
        if ($codes === []) {
            return;
        }

        $deletableIds = Category::query()
            ->where('domain', $domain)
            ->whereNotIn('code', $codes)
            ->whereDoesntHave('children')
            ->whereDoesntHave('usages')
            ->whereNotExists(static function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('category_assignments')
                    ->whereColumn('category_assignments.category_id', 'categories.id');
            })
            ->pluck('id')
            ->all();

        if ($deletableIds !== []) {
            Category::query()
                ->whereKey($deletableIds)
                ->delete();
        }

        self::markStaleDomainCategoriesInactive($domain, $codes);
    }
}
