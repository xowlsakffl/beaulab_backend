<?php

namespace Database\Factories;

use App\Domains\Common\Models\Hashtag\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hashtag>
 */
final class HashtagFactory extends Factory
{
    protected $model = Hashtag::class;

    public function definition(): array
    {
        $name = strtolower($this->faker->unique()->lexify('tag??????'));

        return [
            'name' => $name,
            'normalized_name' => Hashtag::normalizeName($name),
            'status' => Hashtag::STATUS_ACTIVE,
            'usage_count' => 0,
        ];
    }
}
