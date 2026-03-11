<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalTalkComment>
 */
final class HospitalTalkCommentFactory extends Factory
{
    protected $model = HospitalTalkComment::class;

    public function definition(): array
    {
        return [
            'hospital_talk_id' => null,
            'parent_id' => null,
            'author_id' => $this->randomAuthorId(),
            'content' => $this->faker->sentence(18),
            'status' => $this->faker->randomElement([HospitalTalkComment::STATUS_ACTIVE, HospitalTalkComment::STATUS_INACTIVE]),
            'is_visible' => $this->faker->boolean(92),
            'author_ip' => $this->faker->ipv4(),
            'like_count' => $this->faker->numberBetween(0, 80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => HospitalTalkComment::STATUS_ACTIVE,
            'is_visible' => true,
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
