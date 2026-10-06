<?php

namespace Database\Factories;

use App\Domains\Common\Hashtag\Models\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hashtag>
 */
final class HashtagFactory extends Factory
{
    protected $model = Hashtag::class;

    public function definition(): array
    {
        $name = '테스트태그'.$this->faker->unique()->numerify('######');

        return [
            'name' => $name,
            'normalized_name' => Hashtag::normalizeName($name),
            'status' => Hashtag::STATUS_ACTIVE,
            'usage_count' => 0,
        ];
    }
}
