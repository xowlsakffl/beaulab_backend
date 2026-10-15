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
            'author_id' => AccountUser::factory(),
            'content' => $this->faker->randomElement(['후기 잘 읽었습니다. 회복 과정도 궁금해요.', '자세한 후기 공유 감사합니다.', '상담받을 때 참고하겠습니다.']),
            'status' => HospitalReviewComment::STATUS_ACTIVE,
            'author_ip' => $this->faker->ipv4(),
            'like_count' => 0,
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
}
