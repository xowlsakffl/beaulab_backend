<?php

namespace Database\Factories;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeautyExpert>
 */
final class BeautyExpertFactory extends Factory
{
    protected $model = BeautyExpert::class;

    public function definition(): array
    {
        $educationCount = $this->faker->numberBetween(1, 3);
        $careerCount = $this->faker->numberBetween(1, 4);
        $etcCount = $this->faker->numberBetween(0, 2);

        return [
            'beauty_id' => Beauty::factory(),
            'sort_order' => $this->faker->numberBetween(0, 20),
            'name' => fake('ko_KR')->name(),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'position' => $this->faker->randomElement(['원장', '실장', '디자이너', '상담사']),
            'career_started_at' => $this->faker->dateTimeBetween('-15 years', '-1 year')->format('Y-m-d'),
            'educations' => collect(range(1, $educationCount))
                ->map(fn (): string => $this->faker->randomElement(['피부 관리 전문 교육 수료', '미용 실무 교육 과정 수료']))
                ->all(),
            'careers' => collect(range(1, $careerCount))
                ->map(fn (): string => $this->faker->randomElement(['뷰랩 피부관리실 근무', '중앙 뷰티센터 근무']))
                ->all(),
            'etc_contents' => collect(range(1, $etcCount))
                ->map(fn (): string => $this->faker->randomElement(['피부 관리 전문가 과정 이수', '미용 실무 교육 강사']))
                ->all(),
            'status' => $this->faker->randomElement([
                BeautyExpert::STATUS_ACTIVE,
                BeautyExpert::STATUS_SUSPENDED,
                BeautyExpert::STATUS_INACTIVE,
            ]),
            'allow_status' => $this->faker->randomElement([
                BeautyExpert::ALLOW_PENDING,
                BeautyExpert::ALLOW_APPROVED,
                BeautyExpert::ALLOW_REJECTED,
            ]),
        ];
    }

    public function forBeauty(Beauty|int $beauty): self
    {
        return $this->state(fn () => [
            'beauty_id' => $beauty instanceof Beauty ? $beauty->getKey() : $beauty,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'allow_status' => BeautyExpert::ALLOW_APPROVED,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => BeautyExpert::STATUS_ACTIVE,
        ]);
    }

    public function withSeedMedia(): self
    {
        return $this->afterCreating(function (BeautyExpert $expert): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $expert,
                SeedMediaFactory::image("beauty-expert-profile-{$expert->id}"),
                'profile_image',
                'expert',
                'profile-image',
            );

            $mediaAttachAction->attachMany(
                $expert,
                SeedMediaFactory::images("beauty-expert-education-certificate-{$expert->id}", 1),
                'education_certificate_image',
                'expert',
                'education-certificate-image',
            );

            $mediaAttachAction->attachMany(
                $expert,
                SeedMediaFactory::images("beauty-expert-etc-certificate-{$expert->id}", 1),
                'etc_certificate_image',
                'expert',
                'etc-certificate-image',
            );
        });
    }
}
