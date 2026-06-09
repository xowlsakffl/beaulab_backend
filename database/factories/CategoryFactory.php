<?php

namespace Database\Factories;

use App\Domains\Common\Category\Models\Category;
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
            'domain' => Category::DOMAIN_HOSPITAL_REVIEW_SURGERY,
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
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_REVIEW_SURGERY, self::categoryTree('hospital_review_surgery'));
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_REVIEW_TREATMENT, self::categoryTree('hospital_review_treatment'));
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_DOCTER, self::categoryTree('hospital_docter'));
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
     * @param array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string}>}>}> $tree
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
    }

    /**
     * @param array{name:string, code:string, children?:array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string}>}>} $node
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
                'parent_id' => $parent?->id,
                'name' => $name,
            ],
            [
                'depth' => $depth,
                'code' => $node['code'],
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
     * @return array<int, array<string, mixed>>
     */
    private static function categoryTree(string $name): array
    {
        $path = database_path("seeders/data/categories/{$name}.php");
        $tree = require $path;

        if (! is_array($tree)) {
            throw new \RuntimeException("Category seed data must return an array: {$path}");
        }

        return $tree;
    }
}
