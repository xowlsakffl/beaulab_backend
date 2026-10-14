<?php

namespace Database\Factories;

use App\Domains\Common\Category\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $code = $this->faker->unique()->numerify('TEST_########');
        $name = '테스트 진료과목 '.substr($code, 5);

        return [
            'domain' => Category::DOMAIN_HOSPITAL_MEDICAL,
            'parent_id' => null,
            'depth' => 1,
            'group_code' => null,
            'name' => $name,
            'code' => $code,
            'full_path' => $name,
            'sort_order' => 1,
            'status' => Category::STATUS_ACTIVE,
            'is_menu_visible' => true,
        ];
    }
}
