<?php

namespace Database\Factories;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<HospitalVideo>
 */
final class HospitalVideoFactory extends Factory
{
    protected $model = HospitalVideo::class;

    public function definition(): array
    {
        $hospital = $this->randomHospital();
        $allowStatus = $this->faker->randomElement($this->allowStatuses());
        $isUnlimited = $this->faker->boolean(35);
        $publishStartAt = $isUnlimited ? null : $this->faker->dateTimeBetween('-45 days', '+15 days');
        $externalVideoId = $this->youtubeVideoId();
        $allowedAt = in_array($allowStatus, [
            HospitalVideo::ALLOW_APPROVED,
            HospitalVideo::ALLOW_REJECTED,
            HospitalVideo::ALLOW_EXCLUDED,
        ], true)
            ? $this->faker->dateTimeBetween('-30 days', 'now')
            : null;

        return [
            'hospital_id' => (int) $hospital->id,
            'doctor_id' => $this->randomDoctorId($hospital),
            'submitted_by_account_id' => $this->randomSubmittedByAccountId($hospital),
            'title' => $this->faker->randomElement([
                '대표 의료진 인터뷰',
                '병원 시설 소개 영상',
                '시술 전후 관리 안내',
                '상담 프로세스 안내',
                '리얼 후기 숏폼',
            ]),
            'description' => $this->faker->realText(120),
            'is_usage_consented' => $this->faker->boolean(85),
            'distribution_channel' => $this->faker->randomElement([
                HospitalVideo::DISTRIBUTION_CHANNEL_YOUTUBE_APP,
                HospitalVideo::DISTRIBUTION_CHANNEL_APP,
            ]),
            'external_video_id' => $externalVideoId,
            'external_video_url' => "https://www.youtube.com/watch?v={$externalVideoId}",
            'duration_seconds' => $this->faker->numberBetween(30, 900),
            'status' => $this->faker->randomElement([
                HospitalVideo::STATUS_ACTIVE,
                HospitalVideo::STATUS_INACTIVE,
            ]),
            'view_count' => $this->faker->numberBetween(0, 50000),
            'like_count' => $this->faker->numberBetween(0, 3000),
            'publish_start_at' => $publishStartAt,
            'publish_end_at' => $isUnlimited || $publishStartAt === null
                ? null
                : (clone $publishStartAt)->modify('+'.$this->faker->numberBetween(15, 120).' days'),
            'is_publish_period_unlimited' => $isUnlimited,
            'allow_status' => $allowStatus,
            'allowed_at' => $allowedAt,
            'reject_reason' => $allowStatus === HospitalVideo::ALLOW_REJECTED
                ? $this->faker->randomElement(['영상 품질 미흡', '사용 동의 확인 필요', '병원 정보 불일치'])
                : null,
            'reject_reason_detail' => $allowStatus === HospitalVideo::ALLOW_REJECTED
                ? $this->faker->sentence(8)
                : null,
        ];
    }

    public function forHospital(Hospital|int $hospital): self
    {
        $hospitalModel = $hospital instanceof Hospital
            ? $hospital
            : Hospital::query()->findOrFail($hospital);

        return $this->state(fn (): array => [
            'hospital_id' => (int) $hospitalModel->id,
            'doctor_id' => $this->randomDoctorId($hospitalModel),
            'submitted_by_account_id' => $this->randomSubmittedByAccountId($hospitalModel),
        ]);
    }

    public function forDoctor(HospitalDoctor|int $doctor): self
    {
        $doctorModel = $doctor instanceof HospitalDoctor
            ? $doctor
            : HospitalDoctor::query()->findOrFail($doctor);

        return $this->state(fn (): array => [
            'hospital_id' => (int) $doctorModel->hospital_id,
            'doctor_id' => (int) $doctorModel->id,
            'submitted_by_account_id' => $doctorModel->hospital_id
                ? $this->randomSubmittedByAccountId(Hospital::query()->findOrFail($doctorModel->hospital_id))
                : null,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalVideo::STATUS_ACTIVE,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalVideo::ALLOW_APPROVED,
            'allowed_at' => now(),
            'reject_reason' => null,
            'reject_reason_detail' => null,
        ]);
    }

    public function withSeedMedia(bool $includeVideoFile = true): self
    {
        return $this->afterCreating(function (HospitalVideo $video) use ($includeVideoFile): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $video,
                SeedMediaFactory::image("hospital-video-thumbnail-{$video->id}"),
                'thumbnail_file',
                'hospital-video',
                'thumbnail',
                true,
            );

            if (! $includeVideoFile) {
                return;
            }

            $mediaAttachAction->attachOne(
                $video,
                UploadedFile::fake()->create("hospital-video-{$video->id}.mp4", 1024, 'video/mp4'),
                'video_file',
                'hospital-video',
                'video',
                true,
            );
        });
    }

    public function withSeedCategories(int $count = 2): self
    {
        return $this->afterCreating(function (HospitalVideo $video) use ($count): void {
            $categoryIds = Category::query()
                ->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)
                ->where('status', Category::STATUS_ACTIVE)
                ->whereDoesntHave('children')
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();

            if ($categoryIds === []) {
                return;
            }

            $selectedCategoryIds = collect($categoryIds)
                ->shuffle()
                ->take(min(max(1, $count), count($categoryIds)))
                ->values()
                ->all();

            $video->categories()->sync(
                collect($selectedCategoryIds)
                    ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                        $categoryId => ['is_primary' => $index === 0],
                    ])
                    ->all()
            );
        });
    }

    private function randomHospital(): Hospital
    {
        /** @var array<int, int>|null $hospitalIds */
        static $hospitalIds = null;

        if ($hospitalIds === null) {
            $hospitalIds = Hospital::query()
                ->where('status', Hospital::STATUS_ACTIVE)
                ->where('allow_status', Hospital::ALLOW_APPROVED)
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();
        }

        if ($hospitalIds === []) {
            $hospital = Hospital::factory()
                ->active()
                ->approved()
                ->withBusinessRegistration()
                ->withAccountHospital()
                ->withSeedMedia()
                ->create();
            $hospitalIds[] = (int) $hospital->id;

            return $hospital;
        }

        return Hospital::query()->findOrFail($hospitalIds[array_rand($hospitalIds)]);
    }

    private function randomDoctorId(Hospital $hospital): ?int
    {
        $doctorId = HospitalDoctor::query()
            ->where('hospital_id', $hospital->id)
            ->inRandomOrder()
            ->value('id');

        if ($doctorId !== null) {
            return (int) $doctorId;
        }

        $doctor = HospitalDoctor::factory()
            ->forHospital($hospital)
            ->active()
            ->approved()
            ->withSeedMedia()
            ->create();

        return (int) $doctor->id;
    }

    private function randomSubmittedByAccountId(Hospital $hospital): ?int
    {
        $accountId = AccountHospital::query()
            ->where('hospital_id', $hospital->id)
            ->inRandomOrder()
            ->value('id');

        return $accountId === null ? null : (int) $accountId;
    }

    /**
     * @return list<string>
     */
    private function allowStatuses(): array
    {
        return [
            HospitalVideo::ALLOW_SUBMITTED,
            HospitalVideo::ALLOW_IN_REVIEW,
            HospitalVideo::ALLOW_APPROVED,
            HospitalVideo::ALLOW_REJECTED,
            HospitalVideo::ALLOW_EXCLUDED,
            HospitalVideo::ALLOW_PARTNER_CANCELED,
        ];
    }

    private function youtubeVideoId(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 11);
    }
}
