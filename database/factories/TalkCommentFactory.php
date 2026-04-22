<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalkComment>
 */
final class TalkCommentFactory extends Factory
{
    protected $model = TalkComment::class;

    public function definition(): array
    {
        $postStatus = $this->faker->randomElement(TalkComment::postStatuses());

        return [
            'talk_id' => null,
            'parent_id' => null,
            'author_id' => $this->randomAuthorId(),
            'content' => $this->faker->sentence(18),
            'status' => $postStatus === TalkComment::POST_STATUS_NORMAL
                ? $this->faker->randomElement(TalkComment::statuses())
                : TalkComment::STATUS_INACTIVE,
            'post_status' => $postStatus,
            'author_ip' => $this->faker->ipv4(),
            'like_count' => $this->faker->numberBetween(0, 80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => TalkComment::STATUS_ACTIVE,
            'post_status' => TalkComment::POST_STATUS_NORMAL,
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
