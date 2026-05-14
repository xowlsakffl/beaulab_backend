<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends Factory<TalkComment>
 */
final class TalkCommentFactory extends Factory
{
    protected $model = TalkComment::class;

    public function definition(): array
    {
        return [
            'talk_id' => null,
            'parent_id' => null,
            'author_id' => $this->randomAuthorId(),
            'content' => $this->faker->sentence(18),
            'status' => TalkComment::STATUS_ACTIVE,
            'author_ip' => $this->faker->ipv4(),
            'like_count' => $this->faker->numberBetween(0, 80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => TalkComment::STATUS_ACTIVE,
        ]);
    }

    public function topLevel(): self
    {
        return $this->state(fn () => [
            'parent_id' => null,
        ]);
    }

    public function replyTo(TalkComment $parent): self
    {
        if (! $parent->isRootComment()) {
            throw new InvalidArgumentException('토크 댓글은 1단계 대댓글까지만 허용합니다.');
        }

        return $this->state(fn () => [
            'talk_id' => (int) $parent->talk_id,
            'parent_id' => (int) $parent->id,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => [
            'status' => TalkComment::STATUS_INACTIVE,
        ]);
    }

    public function systemBlocked(): self
    {
        return $this->inactive();
    }

    public function autoBlind(): self
    {
        return $this->systemBlocked();
    }

    public function userDeleted(): self
    {
        return $this->inactive();
    }

    public function adminStopped(): self
    {
        return $this->inactive();
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
