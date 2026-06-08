<?php

namespace Database\Factories;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalDoctor>
 */
final class HospitalDoctorFactory extends Factory
{
    protected $model = HospitalDoctor::class;

    public function definition(): array
    {
        $educationCount = $this->faker->numberBetween(1, 3);
        $careerCount = $this->faker->numberBetween(1, 4);
        $etcCount = $this->faker->numberBetween(0, 2);

        return [
            'hospital_id' => Hospital::factory(),
            'sort_order' => $this->faker->numberBetween(0, 20),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement([
                HospitalDoctor::GENDER_MALE,
                HospitalDoctor::GENDER_FEMALE,
            ]),
            'position' => $this->faker->randomElement(HospitalDoctor::positions()),
            'career_started_at' => $this->faker->dateTimeBetween('-15 years', '-1 year')->format('Y-m-d'),
            'license_number' => strtoupper($this->faker->bothify('DOC-########')),
            'specialist_field' => $this->faker->randomElement(HospitalDoctor::specialistFields()),
            'educations' => collect(range(1, $educationCount))
                ->map(fn (): string => $this->faker->company() . ' 수료')
                ->all(),
            'careers' => collect(range(1, $careerCount))
                ->map(fn (): string => $this->faker->company() . ' 근무')
                ->all(),
            'etc_contents' => collect(range(1, $etcCount))
                ->map(fn (): string => $this->faker->sentence(6))
                ->all(),
            'status' => $this->faker->randomElement([
                HospitalDoctor::STATUS_ACTIVE,
                HospitalDoctor::STATUS_SUSPENDED,
                HospitalDoctor::STATUS_INACTIVE,
            ]),
            'allow_status' => $this->faker->randomElement([
                HospitalDoctor::ALLOW_PENDING,
                HospitalDoctor::ALLOW_APPROVED,
                HospitalDoctor::ALLOW_REJECTED,
            ]),
            'view_count' => $this->faker->numberBetween(0, 15000),
        ];
    }

    public function forHospital(Hospital|int $hospital): self
    {
        return $this->state(fn () => [
            'hospital_id' => $hospital instanceof Hospital ? $hospital->getKey() : $hospital,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'allow_status' => HospitalDoctor::ALLOW_APPROVED,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => HospitalDoctor::STATUS_ACTIVE,
        ]);
    }

    public function specialistField(string $specialistField): self
    {
        return $this->state(fn () => [
            'specialist_field' => in_array($specialistField, HospitalDoctor::specialistFields(), true)
                ? $specialistField
                : HospitalDoctor::SPECIALIST_FIELD_NONE,
        ]);
    }

    public function withSeedMedia(): self
    {
        return $this->afterCreating(function (HospitalDoctor $doctor): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $doctor,
                SeedMediaFactory::image("doctor-profile-{$doctor->id}"),
                'profile_image',
                'doctor',
                'profile-image',
            );

            $mediaAttachAction->attachOne(
                $doctor,
                SeedMediaFactory::image("doctor-license-{$doctor->id}"),
                'license_image',
                'doctor',
                'license-image',
            );

            if ($doctor->specialist_field !== HospitalDoctor::SPECIALIST_FIELD_NONE) {
                $mediaAttachAction->attachMany(
                    $doctor,
                    SeedMediaFactory::images("doctor-specialist-certificate-{$doctor->id}", 1),
                    'specialist_certificate_image',
                    'doctor',
                    'specialist-certificate-image',
                );
            }
        });
    }
}
