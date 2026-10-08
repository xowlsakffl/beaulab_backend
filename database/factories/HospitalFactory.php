<?php

namespace Database\Factories;

use App\Common\Authorization\AccessRoles;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hospital>
 */
final class HospitalFactory extends Factory
{
    protected $model = Hospital::class;

    public function definition(): array
    {
        $name = '뷰랩'.fake()->unique()->numerify('######').'의원';
        $departments = Hospital::activeDepartments();
        if ($departments === []) {
            throw new \RuntimeException('활성 분과가 없습니다. CategorySeeder의 기본 분과 준비 여부를 확인해 주세요.');
        }

        return [
            'name' => $name,
            'department' => $this->faker->randomElement($departments),
            'description' => $this->faker->optional(0.7)->randomElement(['충분한 상담을 통해 개인별 진료 계획을 안내합니다.', '상담부터 사후 관리까지 세심하게 안내하는 병의원입니다.']),
            'youtube_link' => $this->faker->optional(0.5)->randomElement([
                'https://www.youtube.com/@beaulabclinic',
                'https://www.youtube.com/@skinclinic',
                'https://www.youtube.com/@plasticclinic',
            ]),
            'address' => fake('ko_KR')->address(),
            'address_detail' => $this->faker->numberBetween(1, 15).'층',

            'latitude' => $this->faker->latitude(33.0, 38.6),
            'longitude' => $this->faker->longitude(124.5, 132.0),

            'tel' => $this->faker->phoneNumber(),
            'ad_reception_phone_1' => $this->faker->numerify('010-####-####'),
            'ad_reception_phone_2' => $this->faker->optional(0.5)->numerify('010-####-####'),
            'ad_reception_phone_3' => $this->faker->optional(0.3)->numerify('010-####-####'),

            'consulting_hours' => $this->faker->optional(0.6)->randomElement(['평일 오전 10시부터 오후 7시까지 상담 가능합니다.', '토요일은 오후 3시까지 상담 가능합니다.']),
            'operation_hours' => $this->defaultOperationHours(),
            'direction' => $this->faker->optional(0.6)->randomElement(['지하철역 2번 출구에서 도보 5분 거리입니다.', '건물 지하 주차장을 이용해 주세요.']),

            'view_count' => $this->faker->numberBetween(0, 50000),

            'allow_status' => $this->faker->randomElement([
                Hospital::ALLOW_PENDING,
                Hospital::ALLOW_REVIEWING,
                Hospital::ALLOW_APPROVED,
                Hospital::ALLOW_REJECTED,
            ]),

            'status' => $this->faker->randomElement([
                Hospital::STATUS_ACTIVE,
                Hospital::STATUS_SUSPENDED,
                Hospital::STATUS_WITHDRAWN,
            ]),
        ];
    }

    public function withAccountHospital(): self
    {
        return $this->afterCreating(function (Hospital $hospital) {
            $seedKey = str_pad((string) $hospital->id, 4, '0', STR_PAD_LEFT);
            $this->createAccountHospital(
                $hospital,
                $seedKey,
                'owner',
                AccessRoles::HOSPITAL_OWNER,
                1
            );
        });
    }

    public function withSeedMedia(int $galleryCount = 5): self
    {
        return $this->afterCreating(function (Hospital $hospital) use ($galleryCount): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $hospital,
                SeedMediaFactory::image("hospital-logo-{$hospital->id}"),
                'logo',
                'hospital',
                'logo',
            );

            $mediaAttachAction->attachMany(
                $hospital,
                SeedMediaFactory::images("hospital-gallery-{$hospital->id}", max(1, $galleryCount)),
                'gallery',
                'hospital',
                'gallery',
                true,
            );

            $businessRegistration = $hospital->businessRegistration()->first();

            if ($businessRegistration instanceof HospitalBusinessRegistration) {
                $mediaAttachAction->attachOne(
                    $businessRegistration,
                    SeedMediaFactory::image("hospital-business-registration-{$hospital->id}"),
                    'business_registration_file',
                    'hospital',
                    'business-registration',
                );
            }
        });
    }

    public function withBusinessRegistration(): self
    {
        return $this->afterCreating(function (Hospital $hospital): void {
            HospitalBusinessRegistration::query()->create([
                'hospital_id' => $hospital->id,
                'business_number' => $this->faker->unique()->numerify('##########'),
                'company_name' => $hospital->name,
                'ceo_name' => fake('ko_KR')->name(),
                'business_type' => $this->faker->randomElement(['의료업', '보건업']),
                'business_item' => $this->faker->randomElement(['성형외과', '피부과', '치과']),
                'business_address' => $hospital->address,
                'business_address_detail' => $hospital->address_detail,
                'settlement_bank_name' => $this->faker->randomElement(['국민은행', '신한은행', '우리은행', '하나은행']),
                'settlement_account_number' => $this->faker->numerify('###-######-#####'),
                'settlement_account_holder' => fake('ko_KR')->name(),
                'tax_invoice_email' => $this->faker->companyEmail(),
                'issued_at' => $this->faker->date(),
                'status' => HospitalBusinessRegistration::STATUS_ACTIVE,
            ]);
        });
    }

    private function createAccountHospital(
        Hospital $hospital,
        string $seedKey,
        string $type,
        string $role,
        int $index
    ): void {
        $rawPassword = (string) config('seeding.staff.password', '');
        $suffix = $type === 'owner' ? '' : str_pad((string) $index, 2, '0', STR_PAD_LEFT);

        $nickname = $type === 'owner'
            ? "hospital_owner_{$seedKey}"
            : "hospital_{$type}_{$seedKey}_{$suffix}";

        $accountHospitalFactory = AccountHospital::factory()
            ->forHospital($hospital)
            ->withNickname($nickname)
            ->withEmail("{$nickname}@example.com")
            ->active();

        if ($rawPassword !== '') {
            $accountHospitalFactory = $accountHospitalFactory->withPassword($rawPassword);
        }

        $accountHospital = $accountHospitalFactory->create();

        $accountHospital->syncRoles([$role]);

    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'allow_status' => Hospital::ALLOW_APPROVED,
        ]);
    }

    public function notApplied(): self
    {
        return $this->state(fn () => [
            'allow_status' => Hospital::ALLOW_NOT_APPLIED,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn () => [
            'status' => Hospital::STATUS_ACTIVE,
        ]);
    }

    private function defaultOperationHours(): array
    {
        return [
            'mon' => ['start' => '10:00', 'end' => '19:00', 'is_closed' => false],
            'tue' => ['start' => '10:00', 'end' => '19:00', 'is_closed' => false],
            'wed' => ['start' => '10:00', 'end' => '19:00', 'is_closed' => false],
            'thu' => ['start' => '10:00', 'end' => '19:00', 'is_closed' => false],
            'fri' => ['start' => '10:00', 'end' => '19:00', 'is_closed' => false],
            'sat' => ['start' => '10:00', 'end' => '15:00', 'is_closed' => false],
            'sun' => ['start' => null, 'end' => null, 'is_closed' => true],
        ];
    }
}
