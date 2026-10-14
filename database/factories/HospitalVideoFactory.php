<?php

namespace Database\Factories;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalVideo>
 */
final class HospitalVideoFactory extends Factory
{
    protected $model = HospitalVideo::class;

    public function definition(): array
    {
        $externalVideoId = $this->youtubeVideoId();

        return [
            'hospital_id' => Hospital::factory()->active()->approved(),
            'doctor_id' => null,
            'manager_staff_id' => fn () => $this->randomManagerStaffId(),
            'title' => $this->faker->randomElement([
                '대표 의료진 인터뷰',
                '병의원 시설 소개',
                '시술 전후 관리 안내',
                '상담 프로세스 안내',
                '리얼 후기 콘텐츠',
            ]),
            'description' => $this->faker->randomElement(['시술 전 상담에서 자주 묻는 질문을 안내합니다.', '시술 과정과 회복 기간 중 주의사항을 설명합니다.']),
            'external_video_url' => "https://www.youtube.com/watch?v={$externalVideoId}",
            'duration_seconds' => $this->faker->numberBetween(30, 1800),
            'hospital_status' => $this->faker->randomElement(HospitalVideo::hospitalStatuses()),
            'admin_status' => $this->faker->randomElement(HospitalVideo::adminStatuses()),
            'view_count' => $this->faker->numberBetween(0, 50000),
            'like_count' => $this->faker->numberBetween(0, 3000),
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
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'hospital_status' => HospitalVideo::HOSPITAL_STATUS_PUBLIC,
            'admin_status' => HospitalVideo::ADMIN_STATUS_NORMAL,
        ]);
    }

    public function approved(): self
    {
        return $this->active();
    }

    public function withSeedMedia(): self
    {
        return $this->afterCreating(function (HospitalVideo $video): void {
            app(MediaAttachDeleteAction::class)->attachOne(
                $video,
                SeedMediaFactory::image("hospital-video-thumbnail-{$video->id}"),
                'thumbnail_file',
                'hospital-video',
                'thumbnail',
                true,
            );
        });
    }

    public function withSeedCategories(int $count = 2): self
    {
        return $this->afterCreating(function (HospitalVideo $video) use ($count): void {
            $categoryIds = Category::query()
                ->where('domain', Category::DOMAIN_HOSPITAL_VIDEO)
                ->where('status', Category::STATUS_ACTIVE)
                ->whereIn('categories.id', app(\App\Domains\Common\Category\Support\CategoryUsageAvailability::class)->ids(CategoryUsage::USAGE_HOSPITAL_VIDEO_CATEGORY))
                ->whereNull('categories.parent_id')
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

            $video->categories()->sync($selectedCategoryIds);
        });
    }

    private function randomDoctorId(Hospital $hospital): ?int
    {
        $doctorId = HospitalDoctor::query()
            ->where('hospital_id', $hospital->id)
            ->where('status', HospitalDoctor::STATUS_ACTIVE)
            ->where('allow_status', HospitalDoctor::ALLOW_APPROVED)
            ->inRandomOrder()
            ->value('id');

        if ($doctorId !== null) {
            return (int) $doctorId;
        }

        return null;
    }

    private function randomManagerStaffId(): ?int
    {
        $staffId = AccountStaff::query()
            ->inRandomOrder()
            ->value('id');

        return $staffId === null ? null : (int) $staffId;
    }

    private function youtubeVideoId(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 11);
    }
}
