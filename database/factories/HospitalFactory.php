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
        $name = $this->faker->unique()->company().' 병원';

        return [
            'name' => $name,
            'department' => $this->faker->randomElement(Hospital::departments()),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'youtube_link' => $this->faker->optional(0.5)->randomElement([
                'https://www.youtube.com/@beaulabclinic',
                'https://www.youtube.com/@skinclinic',
                'https://www.youtube.com/@plasticclinic',
            ]),
            'address' => $this->faker->address(),
            'address_detail' => $this->faker->secondaryAddress(),

            'latitude' => $this->faker->latitude(33.0, 38.6),
            'longitude' => $this->faker->longitude(124.5, 132.0),

            'tel' => $this->faker->phoneNumber(),
            'ad_reception_phone_1' => $this->faker->numerify('010-####-####'),
            'ad_reception_phone_2' => $this->faker->optional(0.5)->numerify('010-####-####'),
            'ad_reception_phone_3' => $this->faker->optional(0.3)->numerify('010-####-####'),

            'consulting_hours' => $this->faker->optional(0.6)->sentence(10),
            'operation_hours' => $this->defaultOperationHours(),
            'direction' => $this->faker->optional(0.6)->sentence(12),

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
                '병원 소유주',
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
                'ceo_name' => $this->faker->name(),
                'business_type' => $this->faker->randomElement(['의료업', '보건업']),
                'business_item' => $this->faker->randomElement(['성형외과', '피부과', '치과']),
                'business_address' => $hospital->address,
                'business_address_detail' => $hospital->address_detail,
                'settlement_bank_name' => $this->faker->randomElement(['국민은행', '신한은행', '우리은행', '하나은행']),
                'settlement_account_number' => $this->faker->numerify('###-######-#####'),
                'settlement_account_holder' => $this->faker->name(),
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
        string $nameLabel,
        string $role,
        int $index
    ): void {
        $rawPassword = (string) config('seeding.staff.password', '');
        $suffix = $type === 'owner' ? '' : str_pad((string) $index, 2, '0', STR_PAD_LEFT);

        $email = $type === 'owner'
            ? "hospital{$seedKey}@owner.test"
            : "hospital{$seedKey}.{$type}{$suffix}@hospital.test";

        $nickname = $type === 'owner'
            ? "hospital_owner_{$seedKey}"
            : "hospital_{$type}_{$seedKey}_{$suffix}";

        $name = $type === 'owner'
            ? "{$nameLabel} {$seedKey}"
            : "{$nameLabel} {$seedKey}-{$suffix}";

        $accountHospitalFactory = AccountHospital::factory()
            ->forHospital($hospital)
            ->withIdentity($name, $nickname, $email)
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
