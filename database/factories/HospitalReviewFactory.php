<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalReview\Models\HospitalReview;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalReview>
 */
final class HospitalReviewFactory extends Factory
{
    protected $model = HospitalReview::class;

    public function definition(): array
    {
        $postStatus = $this->faker->randomElement(HospitalReview::postStatuses());
        $hospitalId = $this->randomHospitalId();

        return [
            'author_id' => $this->randomAuthorId(),
            'hospital_id' => $hospitalId,
            'doctor_id' => $this->randomDoctorId($hospitalId),
            'category_domain' => $this->faker->randomElement(HospitalReview::categoryDomains()),
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraphs(4, true),
            'cost' => $this->faker->numberBetween(20, 1500),
            'rating' => $this->faker->numberBetween(1, 5),
            'status' => $postStatus === HospitalReview::POST_STATUS_NORMAL
                ? $this->faker->randomElement(HospitalReview::statuses())
                : HospitalReview::STATUS_INACTIVE,
            'post_status' => $postStatus,
            'is_main_featured' => $this->faker->boolean(8),
            'is_sub_featured' => $this->faker->boolean(12),
            'view_count' => $this->faker->numberBetween(0, 20000),
            'comment_count' => 0,
            'like_count' => $this->faker->numberBetween(0, 500),
            'save_count' => $this->faker->numberBetween(0, 500),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReview::STATUS_ACTIVE,
            'post_status' => HospitalReview::POST_STATUS_NORMAL,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReview::STATUS_INACTIVE,
            'post_status' => HospitalReview::POST_STATUS_NORMAL,
        ]);
    }

    public function autoBlind(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReview::STATUS_INACTIVE,
            'post_status' => HospitalReview::POST_STATUS_AUTO_BLIND,
        ]);
    }

    public function adminStopped(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReview::STATUS_INACTIVE,
            'post_status' => HospitalReview::POST_STATUS_ADMIN_STOP,
        ]);
    }

    public function userDeleted(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReview::STATUS_INACTIVE,
            'post_status' => HospitalReview::POST_STATUS_USER_DELETE,
        ]);
    }

    public function withSeedMedia(int $beforeImageCount = 2, int $afterImageCount = 2): self
    {
        return $this->afterCreating(function (HospitalReview $review) use ($beforeImageCount, $afterImageCount): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachMany(
                $review,
                SeedMediaFactory::images("hospital-review-before-{$review->id}", max(1, $beforeImageCount)),
                'before_images',
                'hospital-review',
                'before-images',
                true,
            );

            $mediaAttachAction->attachMany(
                $review,
                SeedMediaFactory::images("hospital-review-after-{$review->id}", max(1, $afterImageCount)),
                'after_images',
                'hospital-review',
                'after-images',
                true,
            );
        });
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

    private function randomHospitalId(): int
    {
        /** @var array<int, int>|null $hospitalIds */
        static $hospitalIds = null;

        if ($hospitalIds === null) {
            $hospitalIds = Hospital::query()
                ->where('status', Hospital::STATUS_ACTIVE)
                ->where('allow_status', Hospital::ALLOW_APPROVED)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        if ($hospitalIds === []) {
            $created = Hospital::factory()->active()->approved()->create();
            $hospitalIds[] = (int) $created->id;

            return (int) $created->id;
        }

        return $hospitalIds[array_rand($hospitalIds)];
    }

    private function randomDoctorId(int $hospitalId): ?int
    {
        $doctorIds = HospitalDoctor::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalDoctor::STATUS_ACTIVE)
            ->where('allow_status', HospitalDoctor::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($doctorIds === []) {
            $created = HospitalDoctor::factory()
                ->forHospital($hospitalId)
                ->active()
                ->approved()
                ->create();

            return (int) $created->id;
        }

        return $doctorIds[array_rand($doctorIds)];
    }
}
