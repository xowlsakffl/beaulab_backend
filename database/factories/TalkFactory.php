<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\Talk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talk>
 */
final class TalkFactory extends Factory
{
    protected $model = Talk::class;

    public function definition(): array
    {
        $postStatus = $this->faker->randomElement(Talk::postStatuses());

        return [
            'author_id' => $this->randomAuthorId(),
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraphs(3, true),
            'status' => $postStatus === Talk::POST_STATUS_NORMAL
                ? $this->faker->randomElement(Talk::statuses())
                : Talk::STATUS_INACTIVE,
            'post_status' => $postStatus,
            'author_ip' => $this->faker->ipv4(),
            'is_pinned' => $this->faker->boolean(8),
            'pinned_order' => $this->faker->numberBetween(0, 20),
            'view_count' => $this->faker->numberBetween(0, 5000),
            'comment_count' => 0,
            'like_count' => $this->faker->numberBetween(0, 300),
            'save_count' => $this->faker->numberBetween(0, 300),
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => Talk::STATUS_ACTIVE,
            'post_status' => Talk::POST_STATUS_NORMAL,
        ]);
    }

    public function pinned(): self
    {
        return $this->state(fn () => [
            'is_pinned' => true,
            'pinned_order' => $this->faker->numberBetween(1, 20),
        ]);
    }

    private function randomAuthorId(): int
    {
        /** @var array<int, int>|null $userIds */
        static $userIds = null;

        if ($userIds === null) {
            $userIds = AccountUser::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        }

        if ($userIds === []) {
            $created = AccountUser::factory()->create();
            $userIds[] = (int) $created->id;

            return (int) $created->id;
        }

        return $userIds[array_rand($userIds)];
    }
}
