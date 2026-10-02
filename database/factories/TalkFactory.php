<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Talk\Models\Talk;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talk>
 */
final class TalkFactory extends Factory
{
    protected $model = Talk::class;

    public function definition(): array
    {
        return [
            'author_id' => $this->randomAuthorId(),
            'title' => $this->faker->randomElement(['상담 전에 준비할 사항이 궁금해요', '회복 기간 동안 관리 방법을 공유해요', '병의원 상담 경험을 이야기해 주세요']),
            'content' => $this->faker->randomElement(['상담을 예약했는데 미리 준비할 사항이 있을까요? 경험을 공유해 주세요.', '시술 이후 일상생활로 돌아가기까지의 관리 방법이 궁금합니다.']),
            'status' => Talk::STATUS_ACTIVE,
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
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => [
            'status' => Talk::STATUS_INACTIVE,
        ]);
    }

    public function pinned(): self
    {
        return $this->state(fn () => [
            'is_pinned' => true,
            'pinned_order' => $this->faker->numberBetween(1, 20),
        ]);
    }

    public function withSeedMedia(int $imageCount = 2): self
    {
        return $this->afterCreating(function (Talk $talk) use ($imageCount): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachMany(
                $talk,
                SeedMediaFactory::images(
                    "talk-{$talk->id}",
                    min(Talk::MAX_IMAGE_COUNT, max(1, $imageCount)),
                ),
                'images',
                'talk',
                'images',
                true,
            );
        });
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
