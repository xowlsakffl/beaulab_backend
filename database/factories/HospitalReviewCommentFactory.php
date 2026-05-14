<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends Factory<HospitalReviewComment>
 */
final class HospitalReviewCommentFactory extends Factory
{
    protected $model = HospitalReviewComment::class;

    public function definition(): array
    {
        return [
            'hospital_review_id' => null,
            'parent_id' => null,
            'author_id' => $this->randomAuthorId(),
            'content' => $this->faker->sentence(18),
            'status' => HospitalReviewComment::STATUS_ACTIVE,
            'author_ip' => $this->faker->ipv4(),
            'like_count' => $this->faker->numberBetween(0, 80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReviewComment::STATUS_ACTIVE,
        ]);
    }

    public function topLevel(): self
    {
        return $this->state(fn (): array => [
            'parent_id' => null,
        ]);
    }

    public function replyTo(HospitalReviewComment $parent): self
    {
        if (! $parent->isRootComment()) {
            throw new InvalidArgumentException('병의원 후기 댓글은 1단계 대댓글까지만 허용합니다.');
        }

        return $this->state(fn (): array => [
            'hospital_review_id' => (int) $parent->hospital_review_id,
            'parent_id' => (int) $parent->id,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReviewComment::STATUS_INACTIVE,
        ]);
    }

    public function autoBlind(): self
    {
        return $this->inactive();
    }

    public function adminStopped(): self
    {
        return $this->inactive();
    }

    public function userDeleted(): self
    {
        return $this->inactive();
    }

    private function randomAuthorId(): int
    {
        /** @var array<int, int>|null $userIds */
        static $userIds = null;

        if ($userIds === null) {
            $userIds = AccountUser::query()
                ->where('status', AccountUser::STATUS_ACTIVE)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        if ($userIds === []) {
            $created = AccountUser::factory()->create();
            $userIds[] = (int) $created->id;

            return (int) $created->id;
        }

        return $userIds[array_rand($userIds)];
    }
}
