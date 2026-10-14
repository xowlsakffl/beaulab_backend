<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
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

        return [
            'author_id' => AccountUser::factory(),
            'hospital_id' => Hospital::factory()->active()->approved(),
            'doctor_id' => null,
            'category_domain' => $this->faker->randomElement(HospitalReview::categoryDomains()),
            'title' => $this->faker->randomElement(['상담부터 회복까지의 후기입니다', '시술 후 경과를 공유합니다', '꼼꼼하게 상담받고 결정했어요']),
            'content' => $this->faker->randomElement(['상담에서 시술 방법과 회복 과정을 자세히 안내받았습니다. 안내받은 주의사항을 지키며 경과를 확인하고 있습니다.', '궁금한 점을 충분히 질문할 수 있었고 시술 후 관리 방법도 안내받았습니다.']),
            'author_ip' => $this->faker->ipv4(),
            'cost' => $this->faker->numberBetween(20, 1500),
            'rating' => $this->faker->numberBetween(1, 5),
            'status' => HospitalReview::STATUS_ACTIVE,
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
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalReview::STATUS_INACTIVE,
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

    public function withSmallCategories(?int $count = null): self
    {
        return $this->afterCreating(function (HospitalReview $review) use ($count): void {
            $categoryIds = $this->loadSmallCategoryIdsByDomain((string) $review->category_domain);
            if ($categoryIds === []) {
                throw new \LogicException('후기 카테고리를 먼저 준비해 주세요.');
            }

            $selectedIds = collect($categoryIds)
                ->shuffle()
                ->take($count ?? random_int(1, min(3, count($categoryIds))))
                ->values();

            $review->categories()->sync(
                $selectedIds
                    ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                        $categoryId => ['is_primary' => $index === 0],
                    ])
                    ->all(),
            );
        });
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

    private function loadSmallCategoryIdsByDomain(string $domain): array
    {
        return Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_REVIEW)
            ->where('status', Category::STATUS_ACTIVE)
            ->whereIn('id', app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->ids($domain))
            ->whereDoesntHave('children')
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();
    }
}
